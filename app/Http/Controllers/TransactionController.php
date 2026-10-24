<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all();

        $payments = Payment::with('order')
            ->filter($filters)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totals = Payment::filter($filters)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(amount),0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('trade.transactions.index', compact('payments', 'totals'));
    }

    public function export(Request $request)
    {
        $rows = Payment::with('order')->filter($request->all())->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Receipt', 'Method', 'Phone', 'Amount', 'Status', 'Reference', 'Provider reference']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->created_at->format('Y-m-d H:i'),
                    $p->order->receipt_no ?? '',
                    $p->method,
                    $p->phone,
                    $p->amount,
                    $p->status,
                    $p->external_reference,
                    $p->provider_reference,
                ]);
            }
            fclose($out);
        }, 'transactions-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
}