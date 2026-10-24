
@extends('layouts.app')

@section('title', 'Purchase Details')
@section('page-heading', 'Purchase Details')

@section('content')

@php
    $items = $purchase->items ?? collect();
    $supplierName = $purchase->supplier->name ?? 'Unknown supplier';
    $supplierInitial = strtoupper(substr(trim($supplierName), 0, 1));
    $itemCount = $items->count();
    $totalQuantity = $items->sum('quantity');
@endphp

<style>
    .purchase-detail {
        --pd-bg: #ffffff;
        --pd-soft: #f5f8f6;
        --pd-text: #17251d;
        --pd-muted: #718078;
        --pd-border: #e4ebe6;
        --pd-green: #15803d;
        --pd-green-soft: #eff7f1;
        color: var(--pd-text);
    }

    [data-theme="dark"] .purchase-detail,
    .dark .purchase-detail {
        --pd-bg: #17231b;
        --pd-soft: #1d3325;
        --pd-text: #e5eee8;
        --pd-muted: #9aaba0;
        --pd-border: #2a3a30;
        --pd-green: #4ade80;
        --pd-green-soft: #1d3325;
    }

    .purchase-detail .pd-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 22px;
    }

    .purchase-detail .pd-heading h2 {
        margin: 0 0 5px;
        color: var(--pd-text);
        font-size: 23px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .purchase-detail .pd-heading p {
        margin: 0;
        color: var(--pd-muted);
        font-size: 13px;
    }

    .purchase-detail .pd-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 13px;
        border: 1px solid var(--pd-border);
        border-radius: 9px;
        background: var(--pd-bg);
        color: var(--pd-text);
        text-decoration: none;
        font-size: 12px;
        font-weight: 650;
    }

    .purchase-detail .pd-back:hover {
        background: var(--pd-soft);
    }

    .purchase-detail .pd-alert {
        padding: 12px 15px;
        margin-bottom: 18px;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
    }

    .purchase-detail .pd-overview {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .purchase-detail .pd-info-card {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        padding: 16px;
        border: 1px solid var(--pd-border);
        border-radius: 13px;
        background: var(--pd-bg);
    }

    .purchase-detail .pd-icon {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 11px;
        background: var(--pd-green-soft);
        color: var(--pd-green);
        font-size: 18px;
    }

    .purchase-detail .pd-label {
        margin-bottom: 5px;
        color: var(--pd-muted);
        font-size: 11px;
    }

    .purchase-detail .pd-value {
        overflow-wrap: anywhere;
        color: var(--pd-text);
        font-size: 13px;
        font-weight: 700;
    }

    .purchase-detail .pd-total-card {
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid var(--pd-border);
        border-radius: 14px;
        background: var(--pd-bg);
    }

    .purchase-detail .pd-total-label {
        margin-bottom: 7px;
        color: var(--pd-muted);
        font-size: 12px;
    }

    .purchase-detail .pd-total-value {
        color: var(--pd-green);
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.7px;
    }

    .purchase-detail .pd-total-note {
        margin-top: 5px;
        color: var(--pd-muted);
        font-size: 11px;
    }

    .purchase-detail .pd-table-card {
        overflow: hidden;
        border: 1px solid var(--pd-border);
        border-radius: 14px;
        background: var(--pd-bg);
    }

    .purchase-detail .pd-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 17px 19px;
        border-bottom: 1px solid var(--pd-border);
    }

    .purchase-detail .pd-table-header h3 {
        margin: 0;
        color: var(--pd-text);
        font-size: 14px;
        font-weight: 700;
    }

    .purchase-detail .pd-table-header span {
        color: var(--pd-muted);
        font-size: 12px;
    }

    .purchase-detail .pd-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .purchase-detail table {
        width: 100%;
        min-width: 650px;
        border-collapse: collapse;
    }

    .purchase-detail th {
        padding: 13px 16px;
        background: var(--pd-soft);
        color: var(--pd-muted);
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }

    .purchase-detail td {
        padding: 15px 16px;
        border-top: 1px solid var(--pd-border);
        color: var(--pd-text);
        font-size: 13px;
        vertical-align: middle;
    }

    .purchase-detail tbody tr:hover {
        background: var(--pd-soft);
    }

    .purchase-detail .pd-product {
        font-weight: 650;
    }

    .purchase-detail .pd-quantity {
        display: inline-flex;
        min-width: 34px;
        justify-content: center;
        padding: 5px 8px;
        border-radius: 7px;
        background: var(--pd-green-soft);
        color: var(--pd-green);
        font-size: 12px;
        font-weight: 700;
    }

    .purchase-detail .pd-money {
        white-space: nowrap;
        font-weight: 600;
    }

    .purchase-detail tfoot th {
        padding: 17px 16px;
        border-top: 1px solid var(--pd-border);
        background: var(--pd-soft);
        color: var(--pd-text);
        font-size: 13px;
        text-transform: none;
        letter-spacing: 0;
    }

    .purchase-detail .pd-grand-total {
        color: var(--pd-green);
        font-size: 16px;
        font-weight: 800;
        white-space: nowrap;
    }

    .purchase-detail .pd-empty {
        padding: 40px 20px;
        text-align: center;
        color: var(--pd-muted);
        font-size: 13px;
    }

    .purchase-detail .pd-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .purchase-detail .pd-footer-note {
        color: var(--pd-muted);
        font-size: 12px;
    }

    @media (max-width: 750px) {
        .purchase-detail .pd-overview {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    @media (max-width: 560px) {
        .purchase-detail .pd-heading h2 {
            font-size: 20px;
        }

        .purchase-detail .pd-header {
            align-items: flex-start;
        }

        .purchase-detail .pd-back {
            width: 100%;
            justify-content: center;
        }

        .purchase-detail .pd-total-value {
            font-size: 25px;
        }
    }
</style>

<div class="purchase-detail">

    <div class="pd-header">
        <div class="pd-heading">
            <h2>Purchase Details</h2>
            <p>Review purchase reference, supplier and purchased products.</p>
        </div>

        <a href="{{ route('purchases.index') }}" class="pd-back">
            ← Back to purchases
        </a>
    </div>

    @if(session('status'))
        <div class="pd-alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="pd-overview">

        <div class="pd-info-card">
            <div class="pd-icon">▤</div>
            <div>
                <div class="pd-label">Purchase reference</div>
                <div class="pd-value">{{ $purchase->reference }}</div>
            </div>
        </div>

        <div class="pd-info-card">
            <div class="pd-icon">▦</div>
            <div>
                <div class="pd-label">Purchase date</div>
                <div class="pd-value">
                    {{ $purchase->purchase_date?->format('d M Y') ?? '—' }}
                </div>
            </div>
        </div>

        <div class="pd-info-card">
            <div class="pd-icon">♧</div>
            <div>
                <div class="pd-label">Supplier</div>
                <div class="pd-value">
                    {{ $supplierName }}
                </div>
            </div>
        </div>

    </div>

    <div class="pd-total-card">
        <div class="pd-total-label">Total purchase amount</div>
        <div class="pd-total-value">
            {{ number_format($purchase->total, 2) }}
        </div>
        <div class="pd-total-note">
            {{ number_format($itemCount) }} product lines ·
            {{ number_format($totalQuantity) }} total units
        </div>
    </div>

    <div class="pd-table-card">

        <div class="pd-table-header">
            <h3>Purchased products</h3>
            <span>{{ number_format($itemCount) }} product lines</span>
        </div>

        <div class="pd-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit cost</th>
                        <th>Line total</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <span class="pd-product">
                                    {{ $item->product->name ?? 'Deleted product' }}
                                </span>
                            </td>

                            <td>
                                <span class="pd-quantity">
                                    {{ number_format($item->quantity) }}
                                </span>
                            </td>

                            <td>
                                <span class="pd-money">
                                    {{ number_format($item->unit_cost, 2) }}
                                </span>
                            </td>

                            <td>
                                <span class="pd-money">
                                    {{ number_format($item->line_total, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="pd-empty">
                                    No products were recorded for this purchase.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="3">Purchase total</th>
                        <th>
                            <span class="pd-grand-total">
                                {{ number_format($purchase->total, 2) }}
                            </span>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    <div class="pd-footer">
        <span class="pd-footer-note">
            Purchase reference: {{ $purchase->reference }}
        </span>

        <a href="{{ route('purchases.index') }}" class="pd-back">
            ← All purchases
        </a>
    </div>

</div>

@endsection
