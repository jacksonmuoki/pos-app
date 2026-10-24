
@extends('layouts.app')

@section('title', 'Purchases')
@section('page-heading', 'Purchases')

@section('content')

@php
    $purchases = $purchases ?? collect();

    $totalPurchases = method_exists($purchases, 'total')
        ? $purchases->total()
        : $purchases->count();

    $pageTotal = $purchases->sum('total');
@endphp

<style>
    .purchases-page {
        --pu-bg: #ffffff;
        --pu-soft: #f5f8f6;
        --pu-text: #17251d;
        --pu-muted: #718078;
        --pu-border: #e4ebe6;
        --pu-green: #15803d;
        --pu-green-soft: #eff7f1;
        color: var(--pu-text);
    }

    [data-theme="dark"] .purchases-page,
    .dark .purchases-page {
        --pu-bg: #17231b;
        --pu-soft: #1d3325;
        --pu-text: #e5eee8;
        --pu-muted: #9aaba0;
        --pu-border: #2a3a30;
        --pu-green: #4ade80;
        --pu-green-soft: #1d3325;
    }

    .purchases-page .pu-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .purchases-page .pu-heading h2 {
        margin: 0 0 5px;
        color: var(--pu-text);
        font-size: 23px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .purchases-page .pu-heading p {
        margin: 0;
        color: var(--pu-muted);
        font-size: 13px;
    }

    .purchases-page .pu-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 15px;
        border: 1px solid var(--pu-green);
        border-radius: 9px;
        background: var(--pu-green);
        color: #fff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 650;
        transition: .2s ease;
    }

    .purchases-page .pu-btn:hover {
        filter: brightness(.94);
        transform: translateY(-1px);
    }

    .purchases-page .pu-alert {
        padding: 12px 15px;
        margin-bottom: 18px;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
    }

    .purchases-page .pu-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .purchases-page .pu-stat {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 17px;
        border: 1px solid var(--pu-border);
        border-radius: 13px;
        background: var(--pu-bg);
    }

    .purchases-page .pu-stat-icon {
        display: grid;
        place-items: center;
        width: 43px;
        height: 43px;
        flex-shrink: 0;
        border-radius: 12px;
        background: var(--pu-green-soft);
        color: var(--pu-green);
        font-size: 20px;
    }

    .purchases-page .pu-stat-label {
        margin-bottom: 4px;
        color: var(--pu-muted);
        font-size: 11px;
    }

    .purchases-page .pu-stat-value {
        color: var(--pu-text);
        font-size: 21px;
        font-weight: 750;
    }

    .purchases-page .pu-table-card {
        overflow: hidden;
        border: 1px solid var(--pu-border);
        border-radius: 14px;
        background: var(--pu-bg);
    }

    .purchases-page .pu-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 17px 19px;
        border-bottom: 1px solid var(--pu-border);
    }

    .purchases-page .pu-table-header h3 {
        margin: 0;
        color: var(--pu-text);
        font-size: 14px;
        font-weight: 700;
    }

    .purchases-page .pu-table-header span {
        color: var(--pu-muted);
        font-size: 12px;
    }

    .purchases-page .pu-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .purchases-page table {
        width: 100%;
        min-width: 680px;
        border-collapse: collapse;
    }

    .purchases-page th {
        padding: 13px 16px;
        background: var(--pu-soft);
        color: var(--pu-muted);
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }

    .purchases-page td {
        padding: 15px 16px;
        border-top: 1px solid var(--pu-border);
        color: var(--pu-text);
        font-size: 13px;
        vertical-align: middle;
    }

    .purchases-page tbody tr {
        transition: background .15s ease;
    }

    .purchases-page tbody tr:hover {
        background: var(--pu-soft);
    }

    .purchases-page .pu-reference {
        font-weight: 700;
        color: var(--pu-text);
        white-space: nowrap;
    }

    .purchases-page .pu-date {
        color: var(--pu-muted);
        white-space: nowrap;
        font-size: 12px;
    }

    .purchases-page .pu-supplier {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .purchases-page .pu-avatar {
        display: grid;
        place-items: center;
        width: 31px;
        height: 31px;
        flex-shrink: 0;
        border-radius: 9px;
        background: var(--pu-green-soft);
        color: var(--pu-green);
        font-size: 11px;
        font-weight: 750;
    }

    .purchases-page .pu-supplier-name {
        font-weight: 600;
    }

    .purchases-page .pu-total {
        font-weight: 750;
        white-space: nowrap;
    }

    .purchases-page .pu-view {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 11px;
        border: 1px solid var(--pu-border);
        border-radius: 8px;
        background: var(--pu-bg);
        color: var(--pu-green);
        text-decoration: none;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .purchases-page .pu-view:hover {
        border-color: var(--pu-green);
        background: var(--pu-green-soft);
    }

    .purchases-page .pu-empty {
        padding: 48px 20px;
        text-align: center;
    }

    .purchases-page .pu-empty-icon {
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        margin: 0 auto 13px;
        border-radius: 15px;
        background: var(--pu-soft);
        color: var(--pu-muted);
        font-size: 23px;
    }

    .purchases-page .pu-empty h4 {
        margin: 0 0 6px;
        color: var(--pu-text);
        font-size: 14px;
    }

    .purchases-page .pu-empty p {
        margin: 0 0 15px;
        color: var(--pu-muted);
        font-size: 12px;
    }

    .purchases-page .pu-pagination {
        padding: 15px 18px;
        border-top: 1px solid var(--pu-border);
    }

    @media (max-width: 600px) {
        .purchases-page .pu-heading h2 {
            font-size: 20px;
        }

        .purchases-page .pu-header {
            align-items: flex-start;
        }

        .purchases-page .pu-btn {
            width: 100%;
        }

        .purchases-page .pu-stats {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }
</style>

<div class="purchases-page">

    <div class="pu-header">
        <div class="pu-heading">
            <h2>Purchases</h2>
            <p>Manage supplier purchases and monitor inventory procurement.</p>
        </div>

        <a href="{{ route('purchases.create') }}" class="pu-btn">
            <span>+</span>
            New purchase
        </a>
    </div>

    @if(session('status'))
        <div class="pu-alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="pu-stats">
        <div class="pu-stat">
            <div class="pu-stat-icon">▤</div>
            <div>
                <div class="pu-stat-label">Total purchases</div>
                <div class="pu-stat-value">{{ number_format($totalPurchases) }}</div>
            </div>
        </div>

        <div class="pu-stat">
            <div class="pu-stat-icon">₭</div>
            <div>
                <div class="pu-stat-label">Purchase value on this page</div>
                <div class="pu-stat-value">{{ number_format($pageTotal, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="pu-table-card">

        <div class="pu-table-header">
            <h3>Purchase history</h3>
            <span>{{ number_format($purchases->count()) }} records on this page</span>
        </div>

        <div class="pu-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Purchase date</th>
                        <th>Supplier</th>
                        <th>Total amount</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($purchases as $p)
                        @php
                            $supplierName = $p->supplier->name ?? 'Unknown supplier';
                            $initial = strtoupper(substr(trim($supplierName), 0, 1));
                        @endphp

                        <tr>
                            <td>
                                <span class="pu-reference">
                                    {{ $p->reference }}
                                </span>
                            </td>

                            <td>
                                <span class="pu-date">
                                    {{ $p->purchase_date?->format('d M Y') ?? '—' }}
                                </span>
                            </td>

                            <td>
                                <div class="pu-supplier">
                                    <span class="pu-avatar">{{ $initial }}</span>
                                    <span class="pu-supplier-name">
                                        {{ $supplierName }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="pu-total">
                                    {{ number_format($p->total, 2) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('purchases.show', $p) }}" class="pu-view">
                                    View details →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="pu-empty">
                                    <div class="pu-empty-icon">▤</div>
                                    <h4>No purchases recorded</h4>
                                    <p>Your purchase records will appear here.</p>
                                    <a href="{{ route('purchases.create') }}" class="pu-btn">
                                        + Create first purchase
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($purchases, 'links'))
            <div class="pu-pagination">
                {{ $purchases->links() }}
            </div>
        @endif

    </div>
</div>

@endsection
