<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\CompanySettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Resolve the reporting period from the selected preset or date range.
     */
    private function resolveReportingPeriod(Request $request): array
    {
        $validated = $request->validate([
            'date_preset' => ['nullable', 'in:today,yesterday,tomorrow,custom'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $preset = $validated['date_preset'] ?? null;
        $today = now();

        switch ($preset) {
            case 'today':
                $from = $today->copy()->startOfDay();
                $to = $today->copy()->endOfDay();
                break;

            case 'yesterday':
                $from = $today->copy()->subDay()->startOfDay();
                $to = $from->copy()->endOfDay();
                break;

            case 'tomorrow':
                $from = $today->copy()->addDay()->startOfDay();
                $to = $from->copy()->endOfDay();
                break;

            case 'custom':
                $customDates = $request->validate([
                    'from' => ['required', 'date_format:Y-m-d'],
                    'to' => ['required', 'date_format:Y-m-d'],
                ]);

                $from = Carbon::createFromFormat(
                    'Y-m-d',
                    $customDates['from']
                )->startOfDay();

                $to = Carbon::createFromFormat(
                    'Y-m-d',
                    $customDates['to']
                )->endOfDay();
                break;

            default:
                // Preserve the existing dashboard date-filter behaviour.
                $from = Carbon::parse(
                    $validated['from']
                        ?? $today->copy()->startOfMonth()->toDateString()
                )->startOfDay();

                $to = Carbon::parse(
                    $validated['to']
                        ?? $today->toDateString()
                )->endOfDay();
                break;
        }

        if ($from->gt($to)) {
            return [
                'error' => 'The start date cannot be after the end date.',
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Main Reports & Analytics dashboard.
     */
    public function index(Request $request)
    {
        $dateRange = $this->resolveReportingPeriod($request);

        if (isset($dateRange['error'])) {
            return redirect()
                ->route('reports.index')
                ->with('error', $dateRange['error']);
        }

        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // Previous period: same number of calendar days.
        $periodDays = $from->copy()->startOfDay()
            ->diffInDays($to->copy()->startOfDay()) + 1;

        $previousTo = $from->copy()->subSecond()->endOfDay();

        $previousFrom = $previousTo->copy()
            ->subDays($periodDays - 1)
            ->startOfDay();

        // Paid orders for the selected period.
        $paidOrders = fn () => DB::table('orders')
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to]);

        // Previous-period paid orders.
        $previousPaidOrders = fn () => DB::table('orders')
            ->where('status', 'paid')
            ->whereBetween('created_at', [$previousFrom, $previousTo]);

        // Reusable sales summary.
        $getSalesSummary = function ($query) {
            return $query->selectRaw('
                COUNT(*) as orders,
                COALESCE(SUM(total), 0) as revenue,
                COALESCE(AVG(total), 0) as average,
                COALESCE(SUM(discount), 0) as discounts,
                COALESCE(SUM(tax), 0) as tax
            ')->first();
        };

        // 1. Sales summary.
        $summary = $getSalesSummary($paidOrders());

        // 2. Previous-period comparison.
        $previousSummary = $getSalesSummary($previousPaidOrders());

        $revenueChange = (float) $previousSummary->revenue > 0
            ? (($summary->revenue - $previousSummary->revenue)
                / $previousSummary->revenue) * 100
            : null;

        $ordersChange = (int) $previousSummary->orders > 0
            ? (($summary->orders - $previousSummary->orders)
                / $previousSummary->orders) * 100
            : null;

        // 3. Units sold.
        $unitsSold = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->sum('order_items.qty');

        // 4. Top-selling products.
        $byProduct = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy(
                'order_items.name',
                'products.name',
                'order_items.product_name'
            )
            ->selectRaw("
                COALESCE(
                    NULLIF(order_items.name, ''),
                    products.name,
                    order_items.product_name,
                    'Unknown product'
                ) as name,
                COALESCE(SUM(order_items.qty), 0) as qty,
                COALESCE(SUM(order_items.qty * order_items.price), 0) as revenue
            ")
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // 5. Cashier performance.
        $byCashier = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('users.id', 'users.name')
            ->selectRaw('
                users.name,
                COUNT(orders.id) as orders,
                SUM(orders.total) as revenue
            ')
            ->orderByDesc('revenue')
            ->get();

        // 6. Successful payments by method.
        $byPayment = DB::table('payments')
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('payment_method')
            ->selectRaw('
                payment_method as method,
                COUNT(*) as payments,
                COALESCE(SUM(amount), 0) as total
            ')
            ->orderByDesc('total')
            ->get();

        // 7. Payment status analysis.
        $paymentStatuses = DB::table('payments')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->selectRaw('
                status,
                COUNT(*) as payments,
                COALESCE(SUM(amount), 0) as total
            ')
            ->orderByDesc('payments')
            ->get();

        // 8. Order status analysis.
        $ordersByStatus = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->selectRaw('
                status,
                COUNT(*) as orders,
                COALESCE(SUM(total), 0) as total
            ')
            ->orderByDesc('orders')
            ->get();

        // 9. Daily sales data.
        $dailyRows = DB::table('orders')
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                DATE(created_at) as report_date,
                COUNT(*) as orders,
                COALESCE(SUM(total), 0) as revenue
            ')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('report_date')
            ->get()
            ->keyBy('report_date');

        $salesTrend = [
            'labels' => [],
            'revenue' => [],
            'orders' => [],
        ];

        $period = CarbonPeriod::create(
            $from->copy()->startOfDay(),
            '1 day',
            $to->copy()->startOfDay()
        );

        foreach ($period as $date) {
            $key = $date->toDateString();
            $daily = $dailyRows->get($key);

            $salesTrend['labels'][] = $date->format('d M');
            $salesTrend['revenue'][] = (float) ($daily->revenue ?? 0);
            $salesTrend['orders'][] = (int) ($daily->orders ?? 0);
        }

        // 10. Chart data.
        $productChart = [
            'labels' => $byProduct->take(8)->pluck('name')->values(),
            'values' => $byProduct->take(8)
                ->pluck('qty')
                ->map(fn ($value) => (float) $value)
                ->values(),
        ];

        $cashierChart = [
            'labels' => $byCashier->pluck('name')->values(),
            'values' => $byCashier->pluck('revenue')
                ->map(fn ($value) => (float) $value)
                ->values(),
        ];

        $paymentChart = [
            'labels' => $byPayment->pluck('method')
                ->map(fn ($method) => ucfirst(str_replace('_', ' ', $method)))
                ->values(),
            'values' => $byPayment->pluck('total')
                ->map(fn ($value) => (float) $value)
                ->values(),
        ];

        // 11. Estimated product profitability.
        // Uses current products.cost, not a historical cost snapshot.
        $productProfitability = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy(
                'order_items.name',
                'products.name',
                'order_items.product_name'
            )
            ->selectRaw("
                COALESCE(
                    NULLIF(order_items.name, ''),
                    products.name,
                    order_items.product_name,
                    'Unknown product'
                ) as name,
                COALESCE(SUM(order_items.qty), 0) as qty,
                COALESCE(SUM(order_items.qty * order_items.price), 0) as revenue,
                COALESCE(SUM(order_items.qty * COALESCE(products.cost, 0)), 0) as estimated_cost,
                COALESCE(SUM(
                    order_items.qty * (
                        order_items.price - COALESCE(products.cost, 0)
                    )
                ), 0) as estimated_profit
            ")
            ->orderByDesc('estimated_profit')
            ->limit(10)
            ->get();

        $estimatedGrossProfit = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(
                    order_items.qty * (
                        order_items.price - COALESCE(products.cost, 0)
                    )
                ), 0) as profit
            ')
            ->value('profit');

        $estimatedProfitMargin = (float) $summary->revenue > 0
            ? ($estimatedGrossProfit / $summary->revenue) * 100
            : 0;

        // 12. Customer analytics.
        $totalCustomers = (int) DB::table('customers')->count();

        $newCustomers = (int) DB::table('customers')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $customerOrderStats = DB::table('orders')
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('customer_id')
            ->select('customer_id')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(total) as spending')
            ->groupBy('customer_id')
            ->get();

        $customersWithOrders = $customerOrderStats->count();

        $returningCustomers = $customerOrderStats
            ->where('orders', '>=', 2)
            ->count();

        $topCustomers = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->selectRaw('
                customers.name,
                customers.phone,
                COUNT(orders.id) as orders,
                SUM(orders.total) as spending
            ')
            ->orderByDesc('spending')
            ->limit(10)
            ->get();

        $customerAverageSpend = $customersWithOrders > 0
            ? (float) $customerOrderStats->sum('spending') / $customersWithOrders
            : 0;

        // 13. Purchasing and supplier analytics.
        $purchasesQuery = DB::table('purchases')
            ->whereBetween(
                DB::raw('COALESCE(purchase_date, purchased_at)'),
                [$from, $to]
            );

        $purchaseSummary = (clone $purchasesQuery)
            ->selectRaw('
                COUNT(*) as purchases,
                COALESCE(SUM(total), 0) as total
            ')
            ->first();

        $purchasesBySupplier = DB::table('purchases')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->whereBetween(
                DB::raw('COALESCE(purchases.purchase_date, purchases.purchased_at)'),
                [$from, $to]
            )
            ->groupBy('purchases.supplier_id', 'suppliers.name')
            ->selectRaw("
                COALESCE(suppliers.name, 'Unknown supplier') as supplier,
                COUNT(purchases.id) as purchases,
                SUM(purchases.total) as total
            ")
            ->orderByDesc('total')
            ->get();

        // 14. Inventory valuation.
        // These are current stock figures, not historical daily snapshots.
        $inventorySummary = DB::table('products')
            ->selectRaw('
                COUNT(*) as products,
                COALESCE(SUM(stock), 0) as units_in_stock,
                COALESCE(SUM(stock * cost), 0) as stock_value,
                COALESCE(SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock
            ')
            ->first();

        $lowStock = Product::whereColumn('stock', '<=', 'reorder_level')
            ->orderBy('stock')
            ->limit(20)
            ->get();

        $lowStockCount = (int) Product::whereColumn(
            'stock',
            '<=',
            'reorder_level'
        )->count();

        $outOfStockProducts = Product::where('stock', '<=', 0)
            ->orderBy('name')
            ->limit(20)
            ->get();

        // 15. Products with no sales in the selected period.
        $productSalesQuery = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('order_items.product_id')
            ->selectRaw('
                order_items.product_id,
                COALESCE(SUM(order_items.qty), 0) as qty_sold,
                COALESCE(SUM(order_items.qty * order_items.price), 0) as revenue
            ');

        $noSalesProducts = DB::table('products')
            ->leftJoinSub($productSalesQuery, 'period_sales', function ($join) {
                $join->on('products.id', '=', 'period_sales.product_id');
            })
            ->whereNull('period_sales.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.stock',
                'products.price',
                'products.cost'
            )
            ->orderByDesc('products.stock')
            ->limit(10)
            ->get();

        // 16. Stock adjustment analysis.
        $stockAdjustmentSummary = DB::table('stock_adjustments')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as adjustments,
                COALESCE(SUM(`change`), 0) as net_change,
                COALESCE(SUM(CASE WHEN `change` > 0 THEN `change` ELSE 0 END), 0) as added,
                COALESCE(SUM(CASE WHEN `change` < 0 THEN ABS(`change`) ELSE 0 END), 0) as removed
            ')
            ->first();

        $stockAdjustments = DB::table('stock_adjustments')
            ->leftJoin('products', 'products.id', '=', 'stock_adjustments.product_id')
            ->leftJoin('users', 'users.id', '=', 'stock_adjustments.user_id')
            ->whereBetween('stock_adjustments.created_at', [$from, $to])
            ->select(
                DB::raw('`stock_adjustments`.`change` as `change`'),
                'stock_adjustments.stock_before',
                'stock_adjustments.stock_after',
                'stock_adjustments.reason',
                'stock_adjustments.note',
                'stock_adjustments.created_at',
                'products.name as product_name',
                'users.name as user_name'
            )
            ->orderByDesc('stock_adjustments.created_at')
            ->limit(30)
            ->get();

        // 17. Business insights.
        $insights = [
            'top_product' => $byProduct->first(),
            'top_cashier' => $byCashier->first(),
            'top_payment' => $byPayment->first(),
            'payment_total' => (float) $byPayment->sum('total'),
            'product_revenue' => (float) $byProduct->sum('revenue'),
            'cashier_revenue' => (float) $byCashier->sum('revenue'),
        ];

        // 18. Currency.
        $currency = CompanySettings::currency();

        return view('reports.index', compact(
            'from',
            'to',
            'previousFrom',
            'previousTo',
            'summary',
            'previousSummary',
            'revenueChange',
            'ordersChange',
            'unitsSold',
            'byProduct',
            'byCashier',
            'byPayment',
            'paymentStatuses',
            'ordersByStatus',
            'lowStock',
            'lowStockCount',
            'outOfStockProducts',
            'salesTrend',
            'productChart',
            'cashierChart',
            'paymentChart',
            'insights',
            'productProfitability',
            'estimatedGrossProfit',
            'estimatedProfitMargin',
            'totalCustomers',
            'newCustomers',
            'customersWithOrders',
            'returningCustomers',
            'topCustomers',
            'customerAverageSpend',
            'purchaseSummary',
            'purchasesBySupplier',
            'inventorySummary',
            'noSalesProducts',
            'stockAdjustmentSummary',
            'stockAdjustments',
            'currency'
        ));
    }

    /**
     * Display an individual report in a separate browser tab.
     */
    public function show(Request $request, string $section)
    {
        $dashboard = $this->index($request);

        if (! $dashboard instanceof View) {
            return $dashboard;
        }

        $data = $dashboard->getData();

        $section = $request->query('report', $section);

        $data['section'] = $section;
        $data['reportType'] = $request->query('report_type', 'summary');

        // Current tenant business details.
        $data['businessName'] = CompanySettings::get(
            'business_name',
            config('app.name', 'Business')
        );

        $data['businessPhone'] = CompanySettings::get(
            'business_phone',
            ''
        );

        $data['businessAddress'] = CompanySettings::get(
            'business_address',
            ''
        );

        $data['receiptFooter'] = CompanySettings::get(
            'receipt_footer',
            ''
        );

        $data['currency'] = CompanySettings::currency();

        // Accurate totals across all paid products in the selected period.
        $productTotals = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [
                $data['from'],
                $data['to'],
            ])
            ->selectRaw('
                COALESCE(SUM(order_items.qty), 0) as quantity,
                COALESCE(SUM(order_items.qty * order_items.price), 0) as revenue
            ')
            ->first();

        $data['reportTotals'] = [
            'quantity' => (float) ($productTotals->quantity ?? 0),
            'product_revenue' => (float) ($productTotals->revenue ?? 0),

            'cashier_orders' => (int) $data['byCashier']->sum('orders'),
            'cashier_revenue' => (float) $data['byCashier']->sum('revenue'),

            'payment_count' => (int) $data['byPayment']->sum('payments'),
            'payment_amount' => (float) $data['byPayment']->sum('total'),

            'profit_revenue' => (float) $data['summary']->revenue,
            'profit_cost' => (float) $data['summary']->revenue
                - (float) $data['estimatedGrossProfit'],
            'profit_amount' => (float) $data['estimatedGrossProfit'],

            'purchase_count' => (int) ($data['purchaseSummary']->purchases ?? 0),
            'purchase_amount' => (float) ($data['purchaseSummary']->total ?? 0),
        ];

        return view('reports.single', $data);
    }

    /**
     * Export an individual report as CSV.
     */
    public function exportCsv(Request $request, string $section)
    {
        $dashboard = $this->index($request);

        if (! $dashboard instanceof View) {
            return $dashboard;
        }

        $data = $dashboard->getData();

        $section = $request->query('report', $section);

        $filename = $section . '-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($data, $section) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for compatibility with Excel.
            fwrite($output, "\xEF\xBB\xBF");

            $currency = CompanySettings::currency();

            fputcsv($output, [
                CompanySettings::get('business_name', 'Business'),
            ]);

            fputcsv($output, [
                'Report',
                ucwords(str_replace('-', ' ', $section)),
            ]);

            fputcsv($output, [
                'Period',
                $data['from']->format('d M Y') . ' - ' . $data['to']->format('d M Y'),
            ]);

            fputcsv($output, []);

            switch ($section) {
                case 'sales-breakdown':

                    fputcsv($output, [
                        'Product',
                        'Quantity Sold',
                        'Revenue (' . $currency . ')',
                    ]);

                    foreach ($data['byProduct'] as $row) {
                        fputcsv($output, [
                            $row->name,
                            $row->qty,
                            $row->revenue,
                        ]);
                    }

                    fputcsv($output, [
                        'GRAND TOTAL',
                        $data['unitsSold'],
                        $data['summary']->revenue,
                    ]);

                    break;

                case 'sales-performance':

                    fputcsv($output, [
                        'Cashier',
                        'Orders',
                        'Revenue (' . $currency . ')',
                    ]);

                    foreach ($data['byCashier'] as $row) {
                        fputcsv($output, [
                            $row->name,
                            $row->orders,
                            $row->revenue,
                        ]);
                    }

                    fputcsv($output, [
                        'GRAND TOTAL',
                        $data['byCashier']->sum('orders'),
                        $data['byCashier']->sum('revenue'),
                    ]);

                    break;

                case 'payment-analysis':

                    fputcsv($output, [
                        'Payment Method',
                        'Payments',
                        'Amount (' . $currency . ')',
                    ]);

                    foreach ($data['byPayment'] as $row) {
                        fputcsv($output, [
                            $row->method,
                            $row->payments,
                            $row->total,
                        ]);
                    }

                    fputcsv($output, [
                        'GRAND TOTAL',
                        $data['byPayment']->sum('payments'),
                        $data['byPayment']->sum('total'),
                    ]);

                    break;

                case 'profitability-analysis':

                    fputcsv($output, [
                        'Product',
                        'Quantity',
                        'Revenue',
                        'Estimated Cost',
                        'Estimated Profit',
                    ]);

                    foreach ($data['productProfitability'] as $row) {
                        fputcsv($output, [
                            $row->name,
                            $row->qty,
                            $row->revenue,
                            $row->estimated_cost,
                            $row->estimated_profit,
                        ]);
                    }

                    fputcsv($output, [
                        'TOTAL - DISPLAYED PRODUCTS',
                        $data['productProfitability']->sum('qty'),
                        $data['productProfitability']->sum('revenue'),
                        $data['productProfitability']->sum('estimated_cost'),
                        $data['productProfitability']->sum('estimated_profit'),
                    ]);

                    fputcsv($output, []);

                    fputcsv($output, [
                        'TOTAL ESTIMATED GROSS PROFIT - ALL PRODUCTS',
                        $data['estimatedGrossProfit'],
                    ]);

                    break;

                case 'purchases-suppliers':

                    fputcsv($output, [
                        'Supplier',
                        'Purchases',
                        'Total (' . $currency . ')',
                    ]);

                    foreach ($data['purchasesBySupplier'] as $row) {
                        fputcsv($output, [
                            $row->supplier,
                            $row->purchases,
                            $row->total,
                        ]);
                    }

                    fputcsv($output, [
                        'GRAND TOTAL',
                        $data['purchaseSummary']->purchases,
                        $data['purchaseSummary']->total,
                    ]);

                    break;

                case 'customer-analytics':

                    fputcsv($output, [
                        'Customer',
                        'Phone',
                        'Orders',
                        'Spending (' . $currency . ')',
                    ]);

                    foreach ($data['topCustomers'] as $row) {
                        fputcsv($output, [
                            $row->name,
                            $row->phone,
                            $row->orders,
                            $row->spending,
                        ]);
                    }

                    fputcsv($output, []);

                    fputcsv($output, [
                        'TOTAL CUSTOMERS',
                        $data['totalCustomers'],
                    ]);

                    fputcsv($output, [
                        'CUSTOMERS WITH ORDERS',
                        $data['customersWithOrders'],
                    ]);

                    break;

                case 'inventory-alerts':

                    fputcsv($output, [
                        'Product',
                        'Current Stock',
                        'Reorder Level',
                        'Price',
                        'Cost',
                    ]);

                    foreach ($data['lowStock'] as $row) {
                        fputcsv($output, [
                            $row->name,
                            $row->stock,
                            $row->reorder_level,
                            $row->price,
                            $row->cost,
                        ]);
                    }

                    fputcsv($output, []);

                    fputcsv($output, [
                        'TOTAL PRODUCTS',
                        $data['inventorySummary']->products,
                    ]);

                    fputcsv($output, [
                        'UNITS IN STOCK',
                        $data['inventorySummary']->units_in_stock,
                    ]);

                    fputcsv($output, [
                        'STOCK VALUE',
                        $data['inventorySummary']->stock_value,
                    ]);

                    fputcsv($output, [
                        'OUT OF STOCK PRODUCTS',
                        $data['inventorySummary']->out_of_stock,
                    ]);

                    break;

                case 'inventory-stock-movement':

                    fputcsv($output, [
                        'Date',
                        'Product',
                        'Change',
                        'Stock Before',
                        'Stock After',
                        'Reason',
                        'User',
                    ]);

                    foreach ($data['stockAdjustments'] as $row) {
                        fputcsv($output, [
                            $row->created_at,
                            $row->product_name,
                            $row->change,
                            $row->stock_before,
                            $row->stock_after,
                            $row->reason,
                            $row->user_name,
                        ]);
                    }

                    fputcsv($output, []);

                    fputcsv($output, [
                        'TOTAL ADJUSTMENTS',
                        $data['stockAdjustmentSummary']->adjustments,
                    ]);

                    fputcsv($output, [
                        'NET STOCK CHANGE',
                        $data['stockAdjustmentSummary']->net_change,
                    ]);

                    fputcsv($output, [
                        'STOCK ADDED',
                        $data['stockAdjustmentSummary']->added,
                    ]);

                    fputcsv($output, [
                        'STOCK REMOVED',
                        $data['stockAdjustmentSummary']->removed,
                    ]);

                    break;

                default:

                    fputcsv($output, ['Sales Summary', 'Value']);

                    fputcsv($output, [
                        'Orders',
                        $data['summary']->orders,
                    ]);

                    fputcsv($output, [
                        'Revenue (' . $currency . ')',
                        $data['summary']->revenue,
                    ]);

                    fputcsv($output, [
                        'Average Sale (' . $currency . ')',
                        $data['summary']->average,
                    ]);

                    fputcsv($output, [
                        'Discounts (' . $currency . ')',
                        $data['summary']->discounts,
                    ]);

                    fputcsv($output, [
                        'Tax (' . $currency . ')',
                        $data['summary']->tax,
                    ]);

                    fputcsv($output, [
                        'Units Sold',
                        $data['unitsSold'],
                    ]);

                    break;
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export an individual report as a PDF document.
     */
    public function exportPdf(Request $request, string $section)
    {
        $report = $this->show($request, $section);

        if (! $report instanceof View) {
            return $report;
        }

        // Retrieve the same data used by the browser report.
        $data = $report->getData();

        // Used by reports.single to hide browser-only controls.
        $data['isPdf'] = true;

        $filename = $request->query('report', $section)
            . '-report-'
            . now()->format('Y-m-d-His')
            . '.pdf';

        return Pdf::loadView('reports.single', $data)
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }
}
