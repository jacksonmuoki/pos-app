<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all();

        $orders = Order::with(['user', 'customer'])
            ->filter($filters)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Summary counts paid sales unless a specific status was chosen.
        $summaryQuery = Order::filter($filters);
        if (! $request->filled('status')) {
            $summaryQuery->where('status', 'paid');
        }

        $summary = $summaryQuery->selectRaw(
            'COUNT(*) as orders, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discounts, COALESCE(SUM(tax),0) as tax'
        )->first();

        return view('trade.sales.index', [
            'orders'   => $orders,
            'summary'  => $summary,
            'cashiers' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Order $order)
    {
        return view('trade.sales.show', [
            'order' => $order->load(['items', 'payments', 'user', 'customer']),
        ]);
    }

    public function export(Request $request)
    {
        $rows = Order::with(['user', 'customer'])->filter($request->all())->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt', 'Date', 'Cashier', 'Customer', 'Status', 'Subtotal', 'Discount', 'Tax', 'Total']);
            foreach ($rows as $o) {
                fputcsv($out, [
                    $o->receipt_no,
                    $o->created_at->format('Y-m-d H:i'),
                    $o->user->name ?? '',
                    $o->customer->name ?? '',
                    $o->status,
                    $o->subtotal,
                    $o->discount,
                    $o->tax,
                    $o->total,
                ]);
            }
            fclose($out);
        }, 'sales-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
}