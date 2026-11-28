@php
    $currency = $currency ?? 'KES';
    $pdfMode = $pdfMode ?? false;

    $companyName = $businessName
        ?? $business_name
        ?? data_get($companySettings ?? [], 'business_name')
        ?? config('app.name', 'Business');

    $companyPhone = $businessPhone
        ?? $business_phone
        ?? data_get($companySettings ?? [], 'business_phone')
        ?? '';

    $companyAddress = $businessAddress
        ?? $business_address
        ?? data_get($companySettings ?? [], 'business_address')
        ?? '';

    $footer = $receiptFooter
        ?? $receipt_footer
        ?? data_get($companySettings ?? [], 'receipt_footer')
        ?? '';

    $money = fn ($amount) => $currency . ' ' . number_format((float) $amount, 2);

    $reportNames = [
        'business-overview' => 'Business Overview',
        'sales-performance' => 'Sales Performance',
        'business-insights' => 'Business Insights',
        'sales-breakdown' => 'Sales by Product',
        'payment-analysis' => 'Payment Analysis',
        'inventory-alerts' => 'Inventory Alerts',
        'profitability-analysis' => 'Profitability Analysis',
        'customer-analytics' => 'Customer Analytics',
        'purchases-suppliers' => 'Purchases & Suppliers',
        'inventory-stock-movement' => 'Inventory Stock Movement',
        'transaction-status' => 'Transaction Status',
        'period-comparison' => 'Period Comparison',
    ];

    $title = $reportNames[$section] ?? ucwords(str_replace('-', ' ', $section));

    $summaryOrders = data_get($summary, 'orders', 0);
    $summaryRevenue = data_get($summary, 'revenue', 0);
    $summaryAverage = data_get($summary, 'average', 0);
    $summaryDiscounts = data_get($summary, 'discounts', 0);
    $summaryTax = data_get($summary, 'tax', 0);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }} - {{ $companyName }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #202820;
            margin: 0;
            padding: 0;
            font-size: 13px;
            width: 100%;
            min-height: 100vh;
        }

        .report {
            width: 100%;
            max-width: none;
            min-height: 100vh;
            margin: 0;
            background: #fff;
            padding: 25px 35px;
            border: 0;
            box-shadow: none;
            border-radius: 0;
        }

        /* ATTACHMENTS */

        .actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 25px;
            position: relative;
        }

        .attachments {
            position: relative;
        }

        .attachments summary {
            list-style: none;
            cursor: pointer;
            background: #166534;
            color: white;
            padding: 11px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            user-select: none;
        }

        .attachments summary::-webkit-details-marker {
            display: none;
        }

        .attachments summary:hover {
            background: #14532d;
        }

        .attachments summary::after {
            content: " ▾";
        }

        .attachments[open] summary::after {
            content: " ▴";
        }

        .attachments-menu {
            position: absolute;
            top: calc(100% + 7px);
            right: 0;
            min-width: 180px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            box-shadow: 0 6px 18px rgba(0,0,0,.12);
            padding: 6px;
            z-index: 100;
        }

        .attachments-menu a {
            display: block;
            padding: 11px 13px;
            color: #222;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
        }

        .attachments-menu a:hover {
            background: #f0fdf4;
            color: #166534;
        }

        .attachments-menu a + a {
            border-top: 1px solid #f0f0f0;
        }

        /* BUSINESS HEADER */

        .business-header {
            text-align: center;
            padding-bottom: 22px;
            border-bottom: 2px solid #166534;
            margin-bottom: 25px;
        }

        .business-header h1 {
            color: #166534;
            font-size: 27px;
            margin: 0 0 9px;
            text-transform: uppercase;
        }

        .business-header p {
            margin: 4px 0;
            color: #555;
        }

        /* REPORT HEADING */

        .report-heading {
            text-align: center;
            margin-bottom: 25px;
        }

        .report-heading h2 {
            color: #166534;
            font-size: 21px;
            margin: 0 0 10px;
        }

        .meta {
            color: #555;
            line-height: 1.8;
        }

        /* SECTION HEADINGS */

        h3 {
            color: #166534;
            font-size: 15px;
            margin: 28px 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dce5de;
        }

        /* SUMMARY CARDS */

        .summary {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 8px;
            margin: 18px 0 25px;
        }

        .metric {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            border-radius: 6px;
            padding: 15px;
        }

        .metric small {
            display: block;
            color: #555;
            margin-bottom: 9px;
        }

        .metric strong {
            display: block;
            color: #166534;
            font-size: 17px;
            overflow-wrap: anywhere;
        }

        /* TABLES */

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 20px;
        }

        th,
        td {
            padding: 9px 10px;
            border: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f0fdf4;
            color: #14532d;
            font-weight: 700;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        tfoot td {
            font-weight: bold;
            background: #f0fdf4;
        }

        .number {
            text-align: right;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 20px;
        }

        .note {
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 12px;
            margin: 12px 0;
            color: #78350f;
            border-radius: 5px;
        }

        /* FOOTER */

        .report-footer {
            text-align: center;
            color: #777;
            border-top: 1px solid #ddd;
            margin-top: 35px;
            padding-top: 15px;
            line-height: 1.7;
        }

        /* MOBILE */

        @media(max-width: 700px) {
            .report {
                padding: 18px 12px;
            }

            .summary {
                border-spacing: 5px;
            }

            .metric {
                padding: 9px;
            }

            .metric strong {
                font-size: 14px;
            }

            table {
                font-size: 11px;
            }

            th,
            td {
                padding: 7px 5px;
            }

            .business-header h1 {
                font-size: 21px;
            }
        }

        /* BROWSER PRINT */

        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .report {
                width: 100%;
                min-height: 0;
                padding: 0;
                margin: 0;
            }

            .actions {
                display: none !important;
            }

            h3 {
                page-break-after: avoid;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
            }
        }

        /* DOMPDF-SPECIFIC FORMATTING */

        @if($pdfMode)
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }

            html,
            body {
                width: 100%;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 10px;
                color: #202820;
            }

            .report {
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            .actions,
            .attachments,
            .attachments-menu {
                display: none !important;
            }

            .business-header {
                margin-bottom: 18px;
                padding-bottom: 12px;
            }

            .business-header h1 {
                font-size: 19px;
            }

            .report-heading h2 {
                font-size: 16px;
            }

            h3 {
                font-size: 12px;
                margin-top: 18px;
                page-break-after: avoid;
            }

            .summary {
                display: table;
                width: 100%;
                table-layout: fixed;
                border-spacing: 5px;
            }

            .metric {
                display: table-cell;
                width: 25%;
                padding: 8px;
                background: #f0fdf4;
            }

            .metric strong {
                font-size: 12px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                page-break-inside: auto;
                font-size: 9px;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-row-group;
            }

            tr {
                page-break-inside: avoid;
            }

            th,
            td {
                padding: 6px;
                border: 1px solid #d1d5db;
            }

            th,
            tfoot td {
                background: #eaf5ed;
                color: #14532d;
            }

            .note {
                background: #fffbeb;
                border: 1px solid #e5c76b;
                padding: 8px;
            }

            .report-footer {
                margin-top: 20px;
                padding-top: 10px;
            }
        @endif
    </style>
</head>

<body>

<div class="report">

    {{-- ATTACHMENTS: BROWSER ONLY --}}

    @if(empty($isPdf))
    <div class="actions">
        <details class="attachments">
            <summary>Attachments</summary>

            <div class="attachments-menu">
                <a href="{{ route('reports.export.csv', array_merge(['section' => $section], request()->query())) }}">
                    Export CSV
                </a>

                <a href="{{ route('reports.export.pdf', array_merge(['section' => $section], request()->query())) }}">
                    Export PDF
                </a>
            </div>
        </details>
    </div>
@endif

    {{-- BUSINESS HEADER --}}

    <div class="business-header">
        <h1>{{ $companyName }}</h1>

        @if($companyAddress)
            <p>{{ $companyAddress }}</p>
        @endif

        @if($companyPhone)
            <p>Tel: {{ $companyPhone }}</p>
        @endif
    </div>

    {{-- REPORT INFORMATION --}}

    <div class="report-heading">
        <h2>{{ $title }}</h2>

        <div class="meta">
            <strong>Report Type:</strong>
            {{ ucfirst($reportType ?? 'Summary') }}

            <br>

            <strong>Reporting Period:</strong>
            {{ $from }} to {{ $to }}

            <br>

            <strong>Generated:</strong>
            {{ now()->format('d M Y, H:i') }}
        </div>
    </div>

    {{-- GENERAL SALES SUMMARY --}}

    @if(in_array($section, [
        'business-overview',
        'sales-performance',
        'sales-breakdown',
        'business-insights',
        'payment-analysis',
        'profitability-analysis',
        'customer-analytics',
        'transaction-status',
        'period-comparison'
    ]))

        <h3>Sales Summary</h3>

        <div class="summary">
            <div class="metric">
                <small>Total Orders</small>
                <strong>{{ number_format((float) $summaryOrders) }}</strong>
            </div>

            <div class="metric">
                <small>Total Revenue</small>
                <strong>{{ $money($summaryRevenue) }}</strong>
            </div>

            <div class="metric">
                <small>Average Sale</small>
                <strong>{{ $money($summaryAverage) }}</strong>
            </div>

            <div class="metric">
                <small>Total Discounts</small>
                <strong>{{ $money($summaryDiscounts) }}</strong>
            </div>
        </div>

    @endif

    {{-- SALES BY PRODUCT --}}

    @if($section === 'sales-breakdown')

        <h3>Sales by Product</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="number">Quantity Sold</th>
                    <th class="number">Revenue</th>
                </tr>
            </thead>

            <tbody>
                @forelse($byProduct as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="number">{{ number_format((float) $item->qty) }}</td>
                        <td class="number">{{ $money($item->revenue) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No product sales found.</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="2">Total </td>
                    <td class="number">

                    </td>
                    <td class="number">
                        {{ $money(data_get($reportTotals ?? [], 'product_revenue', $summaryRevenue)) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <p class="meta">
            The table displays the leading products. The grand total represents all products in the reporting period.
        </p>

    @endif

    {{-- SALES PERFORMANCE --}}

    @if($section === 'sales-performance')

        <h3>Sales by Cashier</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cashier</th>
                    <th class="number">Orders</th>
                    <th class="number">Revenue</th>
                </tr>
            </thead>

            <tbody>
                @forelse($byCashier as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="number">{{ number_format((float) $item->orders) }}</td>
                        <td class="number">{{ $money($item->revenue) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No cashier sales found.</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="number">
                        {{ number_format((float) data_get($reportTotals ?? [], 'cashier_orders', 0)) }}
                    </td>
                    <td class="number">
                        {{ $money(data_get($reportTotals ?? [], 'cashier_revenue', 0)) }}
                    </td>
                </tr>
            </tfoot>
        </table>

    @endif

    {{-- PAYMENT ANALYSIS --}}

    @if($section === 'payment-analysis')

        <h3>Payment Method Analysis</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Payment Method</th>
                    <th class="number">Number of Payments</th>
                    <th class="number">Amount</th>
                </tr>
            </thead>

            <tbody>
                @forelse($byPayment as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ ucfirst($item->method) }}</td>
                        <td class="number">{{ number_format((float) $item->payments) }}</td>
                        <td class="number">{{ $money($item->total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No payment records found.</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="number">
                        {{ number_format((float) data_get($reportTotals ?? [], 'payment_count', 0)) }}
                    </td>
                    <td class="number">
                        {{ $money(data_get($reportTotals ?? [], 'payment_amount', 0)) }}
                    </td>
                </tr>
            </tfoot>
        </table>

    @endif

    {{-- PROFITABILITY ANALYSIS --}}

    @if($section === 'profitability-analysis')

        <h3>Product Profitability</h3>

        <div class="note">
            Profit figures are estimates based on the product cost information available in the system.
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="number">Quantity</th>
                    <th class="number">Revenue</th>
                    <th class="number">Estimated Cost</th>
                    <th class="number">Estimated Profit</th>
                </tr>
            </thead>

            <tbody>
                @forelse($productProfitability as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="number">{{ number_format((float) $item->qty) }}</td>
                        <td class="number">{{ $money($item->revenue) }}</td>
                        <td class="number">{{ $money($item->estimated_cost) }}</td>
                        <td class="number">{{ $money($item->estimated_profit) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No profitability records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <div class="metric">
                <small>Estimated Gross Profit</small>
                <strong>{{ $money($estimatedGrossProfit ?? 0) }}</strong>
            </div>

            <div class="metric">
                <small>Estimated Profit Margin</small>
                <strong>{{ number_format((float) ($estimatedProfitMargin ?? 0), 2) }}%</strong>
            </div>
        </div>

    @endif

    {{-- CUSTOMER ANALYTICS --}}

    @if($section === 'customer-analytics')

        <h3>Customer Overview</h3>

        <div class="summary">
            <div class="metric">
                <small>Total Customers</small>
                <strong>{{ number_format((float) ($totalCustomers ?? 0)) }}</strong>
            </div>

            <div class="metric">
                <small>New Customers</small>
                <strong>{{ number_format((float) ($newCustomers ?? 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Customers with Orders</small>
                <strong>{{ number_format((float) ($customersWithOrders ?? 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Returning Customers</small>
                <strong>{{ number_format((float) ($returningCustomers ?? 0)) }}</strong>
            </div>
        </div>

        <h3>Top Customers</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th class="number">Orders</th>
                    <th class="number">Total Spending</th>
                </tr>
            </thead>

            <tbody>
                @forelse($topCustomers as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->phone }}</td>
                        <td class="number">{{ number_format((float) $item->orders) }}</td>
                        <td class="number">{{ $money($item->spending) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">No customer records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @endif

    {{-- PURCHASES AND SUPPLIERS --}}

    @if($section === 'purchases-suppliers')

        <h3>Purchase Summary</h3>

        <div class="summary">
            <div class="metric">
                <small>Total Purchases</small>
                <strong>
                    {{ number_format((float) data_get($purchaseSummary ?? [], 'purchases', 0)) }}
                </strong>
            </div>

            <div class="metric">
                <small>Total Purchase Value</small>
                <strong>
                    {{ $money(data_get($purchaseSummary ?? [], 'total', 0)) }}
                </strong>
            </div>
        </div>

        <h3>Purchases by Supplier</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Supplier</th>
                    <th class="number">Purchases</th>
                    <th class="number">Total Amount</th>
                </tr>
            </thead>

            <tbody>
                @forelse($purchasesBySupplier as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->supplier }}</td>
                        <td class="number">{{ number_format((float) $item->purchases) }}</td>
                        <td class="number">{{ $money($item->total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No supplier purchases found.</td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="2">Total Purchases</td>
                    <td class="number">
                        {{ number_format((float) data_get($purchaseSummary ?? [], 'purchases', 0)) }}
                    </td>
                    <td class="number">
                        {{ $money(data_get($purchaseSummary ?? [], 'total', 0)) }}
                    </td>
                </tr>
            </tfoot>
        </table>

    @endif

    {{-- INVENTORY ALERTS --}}

    @if($section === 'inventory-alerts')

        <h3>Inventory Summary</h3>

        <div class="summary">
            <div class="metric">
                <small>Total Products</small>
                <strong>{{ number_format((float) data_get($inventorySummary ?? [], 'products', 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Units in Stock</small>
                <strong>{{ number_format((float) data_get($inventorySummary ?? [], 'units_in_stock', 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Stock Value</small>
                <strong>{{ $money(data_get($inventorySummary ?? [], 'stock_value', 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Out of Stock</small>
                <strong>{{ number_format((float) data_get($inventorySummary ?? [], 'out_of_stock', 0)) }}</strong>
            </div>
        </div>

        <h3>Low Stock Products</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="number">Current Stock</th>
                    <th class="number">Reorder Level</th>
                </tr>
            </thead>

            <tbody>
                @forelse($lowStock as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ data_get($item, 'name', '—') }}</td>
                        <td class="number">{{ number_format((float) data_get($item, 'stock', 0)) }}</td>
                        <td class="number">{{ number_format((float) data_get($item, 'reorder_level', 0)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No low-stock products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <h3>Out of Stock Products</h3>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                </tr>
            </thead>

            <tbody>
                @forelse($outOfStockProducts as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ data_get($item, 'name', '—') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty">No out-of-stock products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @endif

    {{-- INVENTORY STOCK MOVEMENT --}}

    @if($section === 'inventory-stock-movement')

        <h3>Stock Adjustment Summary</h3>

        <div class="summary">
            <div class="metric">
                <small>Total Adjustments</small>
                <strong>{{ number_format((float) data_get($stockAdjustmentSummary ?? [], 'adjustments', 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Stock Added</small>
                <strong>{{ number_format((float) data_get($stockAdjustmentSummary ?? [], 'added', 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Stock Removed</small>
                <strong>{{ number_format((float) data_get($stockAdjustmentSummary ?? [], 'removed', 0)) }}</strong>
            </div>

            <div class="metric">
                <small>Net Stock Change</small>
                <strong>{{ number_format((float) data_get($stockAdjustmentSummary ?? [], 'net_change', 0)) }}</strong>
            </div>
        </div>

        <h3>Stock Adjustment Details</h3>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th class="number">Change</th>
                    <th class="number">Before</th>
                    <th class="number">After</th>
                    <th>Reason</th>
                    <th>User</th>
                </tr>
            </thead>

            <tbody>
                @forelse($stockAdjustments as $item)
                    <tr>
                        <td>{{ $item->created_at }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td class="number">{{ number_format((float) $item->change) }}</td>
                        <td class="number">{{ number_format((float) $item->stock_before) }}</td>
                        <td class="number">{{ number_format((float) $item->stock_after) }}</td>
                        <td>{{ $item->reason ?? $item->note ?? '—' }}</td>
                        <td>{{ $item->user_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">No stock adjustments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @endif

    {{-- TRANSACTION STATUS --}}

    @if($section === 'transaction-status')

        <h3>Order Status Summary</h3>

        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="number">Number of Orders</th>
                </tr>
            </thead>

            <tbody>
                @forelse($ordersByStatus as $item)
                    <tr>
                        <td>{{ ucfirst(data_get($item, 'status', 'Unknown')) }}</td>
                        <td class="number">
                            {{ number_format((float) data_get($item, 'total', data_get($item, 'orders', 0))) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty">No order status data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <h3>Payment Status Summary</h3>

        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="number">Number of Payments</th>
                </tr>
            </thead>

            <tbody>
                @forelse($paymentStatuses as $item)
                    <tr>
                        <td>{{ ucfirst(data_get($item, 'status', 'Unknown')) }}</td>
                        <td class="number">
                            {{ number_format((float) data_get($item, 'total', data_get($item, 'payments', 0))) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty">No payment status data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @endif

    {{-- BUSINESS INSIGHTS --}}

    @if($section === 'business-insights')

        <h3>Business Insights</h3>

        @forelse($insights as $insight)
            <div class="note">
                {{ is_string($insight) ? $insight : data_get($insight, 'message', data_get($insight, 'title', 'Business insight')) }}
            </div>
        @empty
            <p class="empty">No business insights are available for this period.</p>
        @endforelse

    @endif

    {{-- PERIOD COMPARISON --}}

    @if($section === 'period-comparison')

        <h3>Current Period vs Previous Period</h3>

        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="number">Current Period</th>
                    <th class="number">Previous Period</th>
                    <th class="number">Change</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Revenue</td>
                    <td class="number">{{ $money($summaryRevenue) }}</td>
                    <td class="number">{{ $money(data_get($previousSummary ?? [], 'revenue', 0)) }}</td>
                    <td class="number">{{ number_format((float) ($revenueChange ?? 0), 2) }}%</td>
                </tr>

                <tr>
                    <td>Orders</td>
                    <td class="number">{{ number_format((float) $summaryOrders) }}</td>
                    <td class="number">{{ number_format((float) data_get($previousSummary ?? [], 'orders', 0)) }}</td>
                    <td class="number">{{ number_format((float) ($ordersChange ?? 0), 2) }}%</td>
                </tr>
            </tbody>
        </table>

    @endif

    {{-- BUSINESS OVERVIEW --}}

    @if($section === 'business-overview')

        <h3>Additional Business Information</h3>

        <table>
            <tbody>
                <tr>
                    <th>Units Sold</th>
                    <td>{{ number_format((float) ($unitsSold ?? 0)) }}</td>
                </tr>

                <tr>
                    <th>Tax Collected</th>
                    <td>{{ $money($summaryTax) }}</td>
                </tr>

                <tr>
                    <th>Estimated Gross Profit</th>
                    <td>{{ $money($estimatedGrossProfit ?? 0) }}</td>
                </tr>

                <tr>
                    <th>Estimated Profit Margin</th>
                    <td>{{ number_format((float) ($estimatedProfitMargin ?? 0), 2) }}%</td>
                </tr>

                <tr>
                    <th>Customers</th>
                    <td>{{ number_format((float) ($totalCustomers ?? 0)) }}</td>
                </tr>

                <tr>
                    <th>Purchases</th>
                    <td>{{ number_format((float) data_get($purchaseSummary ?? [], 'purchases', 0)) }}</td>
                </tr>

                <tr>
                    <th>Purchase Value</th>
                    <td>{{ $money(data_get($purchaseSummary ?? [], 'total', 0)) }}</td>
                </tr>
            </tbody>
        </table>

    @endif

    {{-- FOOTER --}}

    <div class="report-footer">
        @if($footer)
            <p>{{ $footer }}</p>
        @endif

        <p>
            {{ $companyName }}
            <br>
            Report generated on {{ now()->format('d M Y, H:i') }}
        </p>
    </div>

</div>

</body>
</html>