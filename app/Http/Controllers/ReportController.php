<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $paidOrders = fn () => DB::table('orders')
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to]);

        $summary = $paidOrders()
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue, COALESCE(AVG(total), 0) as average')
            ->first();

        $byProduct = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('order_items.product_name')
            ->selectRaw('order_items.product_name as name, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as revenue')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $byCashier = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.name, COUNT(orders.id) as orders, SUM(orders.total) as revenue')
            ->orderByDesc('revenue')
            ->get();

        $byPayment = DB::table('payments')
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('method')
            ->selectRaw('method, COUNT(*) as payments, SUM(amount) as total')
            ->orderByDesc('total')
            ->get();

        $lowStock = Product::whereColumn('stock', '<=', 'reorder_level')
            ->orderBy('stock')
            ->limit(20)
            ->get();

        return view('reports.index', compact('from', 'to', 'summary', 'byProduct', 'byCashier', 'byPayment', 'lowStock'));
    }
}