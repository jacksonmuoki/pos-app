<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public const RANGES = [
        'today' => 'Today',
        '7d'    => 'Last 7 days',
        '30d'   => 'Last 30 days',
        'month' => 'This month',
    ];

    public function index(Request $request)
    {
        $user  = auth()->user();
        $cur   = $this->setting('currency', 'KES');
        $money = fn ($v) => $cur . ' ' . number_format((float) $v, 2);

        $range = array_key_exists((string) $request->query('range'), self::RANGES)
            ? (string) $request->query('range')
            : 'today';

        [$from, $to] = $this->bounds($range);
        $days     = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;
        $prevFrom = $from->copy()->subDays($days);
        $prevTo   = $from->copy()->subSecond();

        $s  = $this->summary($from, $to);
        $ps = $this->summary($prevFrom, $prevTo);
        $delta = fn ($now, $before) => $before > 0 ? round(($now - $before) / $before * 100, 1) : null;

        // Payment mix (successful payments only)
        $split = DB::table('payments as p')
            ->join('orders as o', 'o.id', '=', 'p.order_id')
            ->where('o.status', 'paid')
            ->whereBetween('o.paid_at', [$from, $to])
            ->where('p.status', 'success')
            ->selectRaw('p.payment_method AS m, SUM(p.amount) AS s')
            ->groupBy('p.payment_method')
            ->pluck('s', 'm');

        $cash  = (float) ($split['cash'] ?? 0);
        $mpesa = (float) ($split['mpesa'] ?? 0);

        // Trend: hourly for a single day, daily otherwise
        $grain = $days <= 1 ? 'HOUR(paid_at)' : 'DATE(paid_at)';
        $raw   = $this->paid($from, $to)
            ->selectRaw("$grain AS k, SUM(total - discount) AS s")
            ->groupBy('k')
            ->pluck('s', 'k');

        $trend = [];
        if ($days <= 1) {
            for ($h = 0; $h < 24; $h++) {
                $trend[] = ['label' => sprintf('%02d', $h), 'value' => (float) ($raw[$h] ?? 0)];
            }
        } else {
            foreach (CarbonPeriod::create($from, '1 day', $to) as $d) {
                $trend[] = [
                    'label' => $d->format($days > 14 ? 'j M' : 'D j'),
                    'value' => (float) ($raw[$d->toDateString()] ?? 0),
                ];
            }
        }
        $trendMax = max(1, max(array_column($trend, 'value')));

        // Estimated gross profit (uses current product cost)
        $margin    = $this->itemsQuery($from, $to)
            ->selectRaw('COALESCE(SUM(oi.qty * oi.price),0) AS revenue, COALESCE(SUM(oi.qty * COALESCE(p.cost,0)),0) AS cost')
            ->first();
        $revenue   = (float) $margin->revenue;
        $profit    = $revenue - (float) $margin->cost;
        $marginPct = $revenue > 0 ? round($profit / $revenue * 100, 1) : 0;

        $topProducts = $this->itemsQuery($from, $to)
            ->groupBy('oi.name')
            ->selectRaw('oi.name, SUM(oi.qty) AS qty, SUM(oi.qty * oi.price) AS revenue')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get();

        $cashiers = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.status', 'paid')
            ->whereBetween('o.paid_at', [$from, $to])
            ->groupBy('u.id', 'u.name')
            ->selectRaw('u.name, COUNT(*) AS orders, SUM(o.total - o.discount) AS sales')
            ->orderByDesc('sales')
            ->limit(5)
            ->get();

        $recentSales = $this->paid($from, $to)
            ->orderByDesc('paid_at')
            ->limit(8)
            ->get(['id', 'total', 'discount', 'payment_method', 'paid_at']);

        // Live operational state (not limited to the period)
        $pendingMpesa = DB::table('payments')
            ->where('payment_method', 'mpesa')
            ->whereIn('status', ['pending', 'unknown'])
            ->count();

        $openCount = DB::table('orders')->where('status', 'open')->count();
        $heldCount = DB::table('orders')->where('status', 'held')->count();
        $heldOrders = DB::table('orders')->where('status', 'held')
            ->orderBy('created_at')->limit(5)
            ->get(['id', 'table_no', 'total', 'discount', 'created_at']);

        $products       = DB::table('products')->where('active', 1);
        $productCount   = (clone $products)->count();
        $outCount       = (clone $products)->where('stock', '<=', 0)->count();
        $lowCount       = (clone $products)->where('stock', '>', 0)->whereColumn('stock', '<=', 'reorder_level')->count();
        $stockValue     = (float) (clone $products)->selectRaw('COALESCE(SUM(stock * cost),0) AS v')->value('v');
        $stockAlerts    = (clone $products)->whereColumn('stock', '<=', 'reorder_level')
            ->orderBy('stock')->limit(8)->get(['id', 'name', 'stock', 'reorder_level']);

        $purch = Schema::hasColumn('purchases', 'purchase_date')
            ? DB::table('purchases')
                ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total),0) AS s')
                ->first()
            : (object) ['n' => 0, 's' => 0];

        $adjustments = Schema::hasTable('stock_adjustments')
            ? DB::table('stock_adjustments')->whereBetween('created_at', [$from, $to])->count()
            : 0;

        $staffCount = DB::table('users')->where('active', 1)->count();

        $activity = Schema::hasTable('activity_logs')
            ? DB::table('activity_logs as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->orderByDesc('a.created_at')
                ->limit(8)
                ->get(['a.action', 'a.description', 'a.created_at', 'u.name as user_name'])
            : collect();

        $hour     = (int) now()->format('G');
        $greeting = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');

        return view('dashboard', [
            'firstName'    => explode(' ', trim($user->name ?? 'User'))[0],
            'greeting'     => $greeting,
            'ranges'       => self::RANGES,
            'range'        => $range,
            'rangeLabel'   => self::RANGES[$range],
            'money'        => $money,
            'canStaff'     => $user->can('manage staff'),
            'has'          => fn ($name) => Route::has($name),

            's'            => $s,
            'dSales'       => $delta($s['sales'], $ps['sales']),
            'dOrders'      => $delta($s['orders'], $ps['orders']),
            'dAvg'         => $delta($s['avg'], $ps['avg']),
            'discounts'    => $s['discounts'],

            'cash'         => $cash,
            'mpesa'        => $mpesa,
            'cashPct'      => ($cash + $mpesa) > 0 ? round($cash / ($cash + $mpesa) * 100) : 0,

            'trend'        => $trend,
            'trendMax'     => $trendMax,

            'revenue'      => $revenue,
            'profit'       => $profit,
            'marginPct'    => $marginPct,

            'topProducts'  => $topProducts,
            'cashiers'     => $cashiers,
            'recentSales'  => $recentSales,

            'pendingMpesa' => $pendingMpesa,
            'openCount'    => $openCount,
            'heldCount'    => $heldCount,
            'heldOrders'   => $heldOrders,

            'productCount' => $productCount,
            'outCount'     => $outCount,
            'lowCount'     => $lowCount,
            'stockValue'   => $stockValue,
            'stockAlerts'  => $stockAlerts,

            'purchases'    => $purch,
            'adjustments'  => $adjustments,
            'staffCount'   => $staffCount,
            'activity'     => $activity,
        ]);
    }

    private function bounds(string $range): array
    {
        return match ($range) {
            '7d'    => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30d'   => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function paid(Carbon $from, Carbon $to)
    {
        return DB::table('orders')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);
    }

    private function itemsQuery(Carbon $from, Carbon $to)
    {
        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->where('o.status', 'paid')
            ->whereBetween('o.paid_at', [$from, $to]);
    }

    private function summary(Carbon $from, Carbon $to): array
    {
        $r = $this->paid($from, $to)
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total - discount),0) AS sales, COALESCE(SUM(discount),0) AS discounts')
            ->first();

        $orders = (int) $r->orders;
        $sales  = (float) $r->sales;

        return [
            'orders'    => $orders,
            'sales'     => $sales,
            'discounts' => (float) $r->discounts,
            'avg'       => $orders ? $sales / $orders : 0.0,
        ];
    }

    private function setting(string $key, string $default = ''): string
    {
        return (string) (DB::table('settings')->where('key', $key)->value('value') ?? $default);
    }
}