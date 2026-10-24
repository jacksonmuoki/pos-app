<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $receipts = Order::with(['customer', 'user'])
            ->where('status', 'paid')
            ->filter($request->except('status'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('receipts.index', ['receipts' => $receipts]);
    }

    public function show(Order $order)
    {
        return view('receipts.show', $this->receiptData($order));
    }

    public function download(Order $order)
    {
        $html = view('receipts.download', $this->receiptData($order))->render();

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="receipt-' . $order->receipt_no . '.html"',
        ]);
    }

    private function receiptData(Order $order): array
    {
        return [
            'order'       => $order->load(['items', 'payments', 'user', 'customer']),
            'companyName' => DB::table('settings')->where('key', 'company_name')->value('value') ?? 'Point of Sale',
        ];
    }
}