
@extends('layouts.app')

@section('title', 'Sale Details')
@section('page-heading', 'Sale Details')

@section('content')

@php
    $order = $order ?? null;
    $items = $order->items ?? collect();
    $payments = $order->payments ?? collect();

    $status = strtolower($order->status ?? 'pending');

    $statusClasses = [
        'paid' => 'paid',
        'pending' => 'pending',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
    ];

    $statusClass = $statusClasses[$status] ?? 'pending';

    $paymentTotal = $payments->where('status', 'paid')->sum('amount');
@endphp

<style>
.sale-detail {
    --sd-bg: #f5f8f6;
    --sd-card: #ffffff;
    --sd-text: #17251d;
    --sd-muted: #718078;
    --sd-border: #e4ebe6;
    --sd-soft: #eff7f1;
    --sd-green: #15803d;
    --sd-green-dark: #166534;

    width: 100%;
    min-width: 0;
    color: var(--sd-text);
}

html.dark .sale-detail,
body.dark .sale-detail,
[data-theme="dark"] .sale-detail {
    --sd-bg: #101a14;
    --sd-card: #17231b;
    --sd-text: #e5eee8;
    --sd-muted: #9aaba0;
    --sd-border: #2a3a30;
    --sd-soft: #1d3325;
    --sd-green: #4ade80;
    --sd-green-dark: #22c55e;
}

.sd-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 22px;
}

.sd-eyebrow {
    color: var(--sd-green);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .13em;
    text-transform: uppercase;
    margin-bottom: 7px;
}

.sd-title {
    font-size: clamp(23px, 3vw, 31px);
    font-weight: 800;
    letter-spacing: -.04em;
    margin: 0;
    color: var(--sd-text);
    overflow-wrap: anywhere;
}

.sd-description {
    color: var(--sd-muted);
    font-size: 13px;
    margin: 7px 0 0;
}

.sd-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.sd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    border: 1px solid var(--sd-border);
    background: var(--sd-card);
    color: var(--sd-text);
    font-size: 12px;
    font-weight: 750;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.sd-btn:hover {
    background: var(--sd-soft);
    color: var(--sd-green);
    border-color: var(--sd-green);
}

.sd-btn-primary {
    background: var(--sd-green);
    border-color: var(--sd-green);
    color: #fff;
}

.sd-btn-primary:hover {
    background: var(--sd-green-dark);
    color: #fff;
}

.sd-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 11px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.sd-status::before {
    content: "";
    height: 6px;
    width: 6px;
    background: currentColor;
    border-radius: 50%;
}

.sd-status.paid {
    background: #dcfce7;
    color: #15803d;
}

.sd-status.pending {
    background: #fef3c7;
    color: #a16207;
}

.sd-status.cancelled {
    background: #fee2e2;
    color: #b91c1c;
}

.sd-status.refunded {
    background: #ede9fe;
    color: #6d28d9;
}

.sd-info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 19px;
}

.sd-info-card {
    background: var(--sd-card);
    border: 1px solid var(--sd-border);
    border-radius: 14px;
    padding: 16px;
    min-width: 0;
}

.sd-info-label {
    color: var(--sd-muted);
    font-size: 11px;
    font-weight: 700;
}

.sd-info-value {
    color: var(--sd-text);
    font-size: 14px;
    font-weight: 800;
    margin-top: 9px;
    overflow-wrap: anywhere;
}

.sd-info-note {
    color: var(--sd-muted);
    font-size: 11px;
    margin-top: 5px;
}

.sd-panel {
    background: var(--sd-card);
    border: 1px solid var(--sd-border);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 18px;
}

.sd-panel-header {
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    border-bottom: 1px solid var(--sd-border);
}

.sd-panel-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--sd-text);
}

.sd-panel-subtitle {
    color: var(--sd-muted);
    font-size: 11px;
    margin-top: 4px;
}

.sd-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.sd-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 620px;
}

.sd-table th {
    background: var(--sd-bg);
    color: var(--sd-muted);
    text-align: left;
    padding: 13px 17px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.sd-table td {
    padding: 14px 17px;
    border-top: 1px solid var(--sd-border);
    color: var(--sd-text);
    font-size: 12px;
    vertical-align: middle;
}

.sd-table tbody tr:hover {
    background: var(--sd-bg);
}

.sd-product-name {
    font-weight: 750;
    color: var(--sd-text);
}

.sd-product-note {
    color: var(--sd-muted);
    font-size: 11px;
    margin-top: 4px;
}

.sd-number {
    font-variant-numeric: tabular-nums;
}

.sd-total-area {
    display: flex;
    justify-content: flex-end;
    padding: 18px;
    border-top: 1px solid var(--sd-border);
}

.sd-total-box {
    width: min(100%, 340px);
}

.sd-total-row {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 9px 0;
    color: var(--sd-muted);
    font-size: 12px;
}

.sd-total-row strong {
    color: var(--sd-text);
    font-weight: 750;
    font-variant-numeric: tabular-nums;
}

.sd-total-final {
    margin-top: 7px;
    padding-top: 15px;
    border-top: 1px solid var(--sd-border);
    color: var(--sd-text);
    font-size: 14px;
    font-weight: 800;
}

.sd-total-final strong {
    color: var(--sd-green);
    font-size: 20px;
}

.sd-payment-method {
    display: inline-flex;
    padding: 5px 9px;
    border-radius: 7px;
    background: var(--sd-soft);
    color: var(--sd-green);
    font-size: 10px;
    font-weight: 800;
}

.sd-empty {
    padding: 35px 20px;
    text-align: center;
    color: var(--sd-muted);
    font-size: 12px;
}

.sd-payment-footer {
    display: flex;
    justify-content: flex-end;
    padding: 14px 18px;
    border-top: 1px solid var(--sd-border);
    font-size: 12px;
    color: var(--sd-muted);
}

.sd-payment-footer strong {
    color: var(--sd-green);
    margin-left: 8px;
}

@media(max-width: 850px) {
    .sd-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 500px) {
    .sd-heading {
        align-items: stretch;
    }

    .sd-actions {
        width: 100%;
    }

    .sd-actions .sd-btn {
        flex: 1;
    }

    .sd-info-grid {
        gap: 9px;
    }

    .sd-info-card {
        padding: 12px;
    }

    .sd-info-value {
        font-size: 12px;
    }

    .sd-total-box {
        width: 100%;
    }
}
</style>

<div class="sale-detail">

    {{-- HEADER --}}
    <div class="sd-heading">

        <div>
            <div class="sd-eyebrow">Sales management / Transaction details</div>

            <h1 class="sd-title">
                Sale {{ $order->receipt_no }}
            </h1>

            <p class="sd-description">
                Detailed transaction information, purchased items and payments.
            </p>
        </div>

        <div class="sd-actions">

            <a href="{{ route('sales.index') }}" class="sd-btn">
                ← Back to sales
            </a>

            @if($order->status === 'paid')
                <a href="{{ route('receipts.show', $order) }}"
                   class="sd-btn sd-btn-primary">
                    <span>▤</span> View receipt
                </a>
            @endif

        </div>

    </div>

    {{-- SALE INFORMATION --}}
    <div class="sd-info-grid">

        <div class="sd-info-card">
            <div class="sd-info-label">Receipt number</div>
            <div class="sd-info-value">{{ $order->receipt_no }}</div>
            <div class="sd-info-note">Transaction reference</div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Transaction date</div>
            <div class="sd-info-value">
                {{ $order->created_at?->format('d M Y') ?? '-' }}
            </div>
            <div class="sd-info-note">
                {{ $order->created_at?->format('H:i') ?? '-' }}
            </div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Cashier</div>
            <div class="sd-info-value">
                {{ $order->user->name ?? '-' }}
            </div>
            <div class="sd-info-note">Processed by</div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Sale status</div>
            <div class="sd-info-value">
                <span class="sd-status {{ $statusClass }}">
                    {{ ucfirst($status) }}
                </span>
            </div>
            <div class="sd-info-note">
                Current transaction status
            </div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Customer</div>
            <div class="sd-info-value">
                {{ $order->customer->name ?? 'Walk-in Customer' }}
            </div>
            <div class="sd-info-note">Customer information</div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Products</div>
            <div class="sd-info-value">
                {{ number_format($items->count()) }}
            </div>
            <div class="sd-info-note">Different line items</div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Total quantity</div>
            <div class="sd-info-value">
                {{ number_format($items->sum('quantity')) }}
            </div>
            <div class="sd-info-note">Units purchased</div>
        </div>

        <div class="sd-info-card">
            <div class="sd-info-label">Amount paid</div>
            <div class="sd-info-value">
                {{ number_format((float) $paymentTotal, 2) }}
            </div>
            <div class="sd-info-note">Successful payments</div>
        </div>

    </div>

    {{-- PRODUCTS --}}
    <div class="sd-panel">

        <div class="sd-panel-header">
            <div>
                <div class="sd-panel-title">Purchased products</div>
                <div class="sd-panel-subtitle">
                    Items included in this transaction.
                </div>
            </div>

            <span class="sd-panel-subtitle">
                {{ $items->count() }} line items
            </span>
        </div>

        <div class="sd-table-wrap">

            <table class="sd-table">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit price</th>
                        <th style="text-align:right">Line total</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($items as $item)

                        <tr>
                            <td>
                                <div class="sd-product-name">
                                    {{ $item->product_name }}
                                </div>
                            </td>

                            <td class="sd-number">
                                {{ number_format((float) $item->quantity, 2) }}
                            </td>

                            <td class="sd-number">
                                {{ number_format((float) $item->unit_price, 2) }}
                            </td>

                            <td style="text-align:right">
                                <strong class="sd-number">
                                    {{ number_format((float) $item->line_total, 2) }}
                                </strong>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="4">
                                <div class="sd-empty">
                                    No products recorded for this sale.
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- TOTALS --}}
        <div class="sd-total-area">

            <div class="sd-total-box">

                <div class="sd-total-row">
                    <span>Subtotal</span>
                    <strong>
                        {{ number_format((float) $order->subtotal, 2) }}
                    </strong>
                </div>

                <div class="sd-total-row">
                    <span>Discount</span>
                    <strong>
                        -{{ number_format((float) $order->discount, 2) }}
                    </strong>
                </div>

                <div class="sd-total-row">
                    <span>Tax</span>
                    <strong>
                        {{ number_format((float) $order->tax, 2) }}
                    </strong>
                </div>

                <div class="sd-total-row sd-total-final">
                    <span>Total amount</span>
                    <strong>
                        {{ number_format((float) $order->total, 2) }}
                    </strong>
                </div>

            </div>

        </div>

    </div>

    {{-- PAYMENTS --}}
    <div class="sd-panel">

        <div class="sd-panel-header">
            <div>
                <div class="sd-panel-title">Payment records</div>
                <div class="sd-panel-subtitle">
                    Payment methods and provider transaction references.
                </div>
            </div>

            <span class="sd-panel-subtitle">
                {{ $payments->count() }} payment records
            </span>
        </div>

        <div class="sd-table-wrap">

            <table class="sd-table">

                <thead>
                    <tr>
                        <th>Payment method</th>
                        <th>Amount</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Reference</th>
                        <th>Paid at</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($payments as $payment)

                        @php
                            $paymentStatus = strtolower($payment->status ?? 'pending');
                            $paymentStatusClass = $statusClasses[$paymentStatus] ?? 'pending';
                        @endphp

                        <tr>

                            <td>
                                <span class="sd-payment-method">
                                    {{ strtoupper($payment->method ?? 'N/A') }}
                                </span>
                            </td>

                            <td>
                                <strong class="sd-number">
                                    {{ number_format((float) $payment->amount, 2) }}
                                </strong>
                            </td>

                            <td>
                                {{ $payment->phone ?: '—' }}
                            </td>

                            <td>
                                <span class="sd-status {{ $paymentStatusClass }}">
                                    {{ ucfirst($paymentStatus) }}
                                </span>
                            </td>

                            <td>
                                <span class="sd-number">
                                    {{ $payment->provider_reference
                                        ?: ($payment->external_reference ?: '—') }}
                                </span>
                            </td>

                            <td>
                                {{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6">
                                <div class="sd-empty">
                                    No payments recorded for this sale.
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="sd-payment-footer">
            Successful payments:
            <strong>{{ number_format((float) $paymentTotal, 2) }}</strong>
        </div>

    </div>

    {{-- BOTTOM ACTIONS --}}
    <div class="sd-actions" style="justify-content:flex-end;margin-bottom:20px;">

        <a href="{{ route('sales.index') }}" class="sd-btn">
            ← Back to sales
        </a>

        @if($order->status === 'paid')
            <a href="{{ route('receipts.show', $order) }}"
               class="sd-btn sd-btn-primary">
                View receipt →
            </a>
        @endif

    </div>

</div>

@endsection
