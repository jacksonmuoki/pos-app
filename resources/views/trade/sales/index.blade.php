
@extends('layouts.app')

@section('title', 'Sales')
@section('page-heading', 'Sales Management')

@section('content')

@php
    $summary = $summary ?? (object) [
        'orders' => 0,
        'revenue' => 0,
        'discounts' => 0,
        'tax' => 0,
    ];

    $cashiers = $cashiers ?? collect();
    $orders = $orders ?? collect();

    $filtersActive = request()->hasAny([
        'from', 'to', 'status', 'user_id', 'method', 'search'
    ]);

    $statuses = [
        'paid' => 'Paid',
        'pending' => 'Pending',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];

    $statusStyles = [
        'paid' => 'paid',
        'pending' => 'pending',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
    ];
@endphp

<style>
.sales-page {
    --s-bg: #f5f8f6;
    --s-card: #fff;
    --s-text: #17251d;
    --s-muted: #718078;
    --s-border: #e4ebe6;
    --s-soft: #eff7f1;
    --s-green: #15803d;
    --s-green-dark: #166534;

    width: 100%;
    min-width: 0;
    color: var(--s-text);
}

html.dark .sales-page,
body.dark .sales-page,
[data-theme="dark"] .sales-page {
    --s-bg: #101a14;
    --s-card: #17231b;
    --s-text: #e5eee8;
    --s-muted: #9aaba0;
    --s-border: #2a3a30;
    --s-soft: #1d3325;
    --s-green: #4ade80;
    --s-green-dark: #22c55e;
}

.sales-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 23px;
}

.sales-eyebrow {
    color: var(--s-green);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .13em;
    text-transform: uppercase;
    margin-bottom: 7px;
}

.sales-title {
    color: var(--s-text);
    font-size: clamp(24px, 3vw, 32px);
    font-weight: 800;
    letter-spacing: -.04em;
    margin: 0;
}

.sales-description {
    color: var(--s-muted);
    font-size: 13px;
    margin: 7px 0 0;
}

.sales-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    border: 1px solid var(--s-border);
    background: var(--s-card);
    color: var(--s-text);
    text-decoration: none;
    font-size: 12px;
    font-weight: 750;
    cursor: pointer;
    transition: .2s ease;
    white-space: nowrap;
}

.sales-btn:hover {
    background: var(--s-soft);
    color: var(--s-green);
    border-color: var(--s-green);
}

.sales-btn-primary {
    background: var(--s-green);
    color: #fff;
    border-color: var(--s-green);
}

.sales-btn-primary:hover {
    background: var(--s-green-dark);
    color: #fff;
}

.sales-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}

.sales-stat {
    background: var(--s-card);
    border: 1px solid var(--s-border);
    border-radius: 15px;
    padding: 17px;
    min-width: 0;
}

.sales-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.sales-stat-label {
    font-size: 12px;
    font-weight: 650;
    color: var(--s-muted);
}

.sales-stat-icon {
    display: grid;
    place-items: center;
    width: 35px;
    height: 35px;
    border-radius: 10px;
    background: var(--s-soft);
    color: var(--s-green);
    font-size: 16px;
    font-weight: 800;
}

.sales-stat-value {
    font-size: clamp(20px, 2.5vw, 25px);
    font-weight: 800;
    letter-spacing: -.04em;
    color: var(--s-text);
    margin-top: 12px;
    overflow-wrap: anywhere;
}

.sales-stat-note {
    color: var(--s-muted);
    font-size: 11px;
    margin-top: 4px;
}

.sales-panel {
    background: var(--s-card);
    border: 1px solid var(--s-border);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 18px;
}

.sales-panel-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--s-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.sales-panel-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--s-text);
}

.sales-panel-subtitle {
    color: var(--s-muted);
    font-size: 11px;
    margin-top: 4px;
}

.sales-filter-form {
    padding: 17px;
}

.sales-filter-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
}

.sales-field {
    min-width: 0;
}

.sales-field label {
    display: block;
    color: var(--s-muted);
    font-size: 11px;
    font-weight: 750;
    margin-bottom: 7px;
}

.sales-control {
    width: 100%;
    height: 41px;
    min-width: 0;
    border: 1px solid var(--s-border);
    border-radius: 9px;
    background: var(--s-card);
    color: var(--s-text);
    padding: 0 11px;
    outline: none;
    font-size: 12px;
}

.sales-control:focus {
    border-color: var(--s-green);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--s-green) 13%, transparent);
}

.sales-filter-bottom {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
}

.sales-search-field {
    flex: 1 1 260px;
    min-width: 180px;
}

.sales-filter-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.sales-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.sales-table th {
    padding: 13px 16px;
    text-align: left;
    white-space: nowrap;
    background: var(--s-bg);
    color: var(--s-muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 800;
}

.sales-table td {
    padding: 14px 16px;
    border-top: 1px solid var(--s-border);
    color: var(--s-text);
    font-size: 12px;
    vertical-align: middle;
}

.sales-table tbody tr {
    transition: background .15s ease;
}

.sales-table tbody tr:hover {
    background: var(--s-bg);
}

.sales-receipt {
    color: var(--s-green);
    font-weight: 800;
    text-decoration: none;
}

.sales-receipt:hover {
    text-decoration: underline;
}

.sales-primary-text {
    font-weight: 750;
    color: var(--s-text);
}

.sales-secondary-text {
    font-size: 11px;
    color: var(--s-muted);
    margin-top: 4px;
}

.sales-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.sales-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.sales-status.paid {
    color: #15803d;
    background: #dcfce7;
}

.sales-status.pending {
    color: #a16207;
    background: #fef3c7;
}

.sales-status.cancelled {
    color: #b91c1c;
    background: #fee2e2;
}

.sales-status.refunded {
    color: #6d28d9;
    background: #ede9fe;
}

.sales-method {
    display: inline-flex;
    padding: 4px 8px;
    border-radius: 7px;
    background: var(--s-soft);
    color: var(--s-green);
    font-size: 10px;
    font-weight: 750;
    text-transform: uppercase;
}

.sales-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 7px;
}

.sales-action {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-height: 31px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--s-border);
    background: var(--s-card);
    color: var(--s-text);
    font-size: 11px;
    font-weight: 750;
    text-decoration: none;
    white-space: nowrap;
}

.sales-action:hover {
    background: var(--s-soft);
    color: var(--s-green);
    border-color: var(--s-green);
}

.sales-empty {
    text-align: center;
    padding: 55px 20px;
    color: var(--s-muted);
}

.sales-empty-icon {
    display: grid;
    place-items: center;
    width: 54px;
    height: 54px;
    border-radius: 16px;
    margin: 0 auto 13px;
    background: var(--s-soft);
    color: var(--s-green);
    font-size: 23px;
}

.sales-empty strong {
    color: var(--s-text);
    font-size: 14px;
}

.sales-empty p {
    font-size: 12px;
    margin: 6px 0 15px;
}

.sales-pagination {
    padding: 15px 17px;
    border-top: 1px solid var(--s-border);
}

.sales-pagination nav {
    display: flex;
    justify-content: flex-end;
}

.sales-pagination svg {
    width: 16px;
    height: 16px;
}

.sales-pagination p {
    color: var(--s-muted);
    font-size: 12px;
}

.sales-mobile-hint {
    display: none;
    padding: 9px 15px;
    font-size: 11px;
    color: var(--s-muted);
    border-bottom: 1px solid var(--s-border);
}

@media(max-width: 950px) {
    .sales-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sales-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 560px) {
    .sales-stats {
        gap: 9px;
    }

    .sales-stat {
        padding: 13px;
    }

    .sales-stat-value {
        font-size: 20px;
    }

    .sales-filter-grid {
        grid-template-columns: 1fr;
    }

    .sales-filter-actions {
        width: 100%;
    }

    .sales-filter-actions .sales-btn {
        flex: 1;
    }

    .sales-filter-bottom {
        align-items: stretch;
    }

    .sales-search-field {
        flex-basis: 100%;
    }

    .sales-heading > .sales-btn {
        width: 100%;
    }

    .sales-mobile-hint {
        display: block;
    }
}
</style>

<div class="sales-page">

    {{-- PAGE HEADING --}}
    <div class="sales-heading">
        <div>
            <div class="sales-eyebrow">Sales management / Transactions</div>
            <h1 class="sales-title">Sales</h1>
            <p class="sales-description">
                Monitor transactions, revenue and payment activity.
            </p>
        </div>

        <a href="{{ route('sales.export', request()->query()) }}"
           class="sales-btn sales-btn-primary">
            <span>↓</span> Export CSV
        </a>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="sales-stats">

        <div class="sales-stat">
            <div class="sales-stat-top">
                <span class="sales-stat-label">Total Sales</span>
                <span class="sales-stat-icon">↗</span>
            </div>
            <div class="sales-stat-value">
                {{ number_format($summary->orders) }}
            </div>
            <div class="sales-stat-note">Matching transactions</div>
        </div>

        <div class="sales-stat">
            <div class="sales-stat-top">
                <span class="sales-stat-label">Total Revenue</span>
                <span class="sales-stat-icon">KSh</span>
            </div>
            <div class="sales-stat-value">
                {{ number_format((float) $summary->revenue, 2) }}
            </div>
            <div class="sales-stat-note">Sales revenue</div>
        </div>

        <div class="sales-stat">
            <div class="sales-stat-top">
                <span class="sales-stat-label">Discounts</span>
                <span class="sales-stat-icon">%</span>
            </div>
            <div class="sales-stat-value">
                {{ number_format((float) $summary->discounts, 2) }}
            </div>
            <div class="sales-stat-note">Total discounts applied</div>
        </div>

        <div class="sales-stat">
            <div class="sales-stat-top">
                <span class="sales-stat-label">Tax Collected</span>
                <span class="sales-stat-icon">T</span>
            </div>
            <div class="sales-stat-value">
                {{ number_format((float) $summary->tax, 2) }}
            </div>
            <div class="sales-stat-note">Tax across matching sales</div>
        </div>

    </div>

    {{-- FILTERS --}}
    <div class="sales-panel">

        <div class="sales-panel-header">
            <div>
                <div class="sales-panel-title">Filter transactions</div>
                <div class="sales-panel-subtitle">
                    Narrow down sales by date, cashier, payment method or status.
                </div>
            </div>

            @if($filtersActive)
                <span class="sales-status paid">Filters applied</span>
            @endif
        </div>

        <form method="GET"
              action="{{ route('sales.index') }}"
              class="sales-filter-form">

            <div class="sales-filter-grid">

                <div class="sales-field">
                    <label for="sales-from">From date</label>
                    <input id="sales-from"
                           type="date"
                           name="from"
                           value="{{ request('from') }}"
                           class="sales-control">
                </div>

                <div class="sales-field">
                    <label for="sales-to">To date</label>
                    <input id="sales-to"
                           type="date"
                           name="to"
                           value="{{ request('to') }}"
                           class="sales-control">
                </div>

                <div class="sales-field">
                    <label for="sales-status">Payment status</label>
                    <select id="sales-status" name="status" class="sales-control">
                        <option value="">Paid (default)</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}"
                                @selected(request('status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sales-field">
                    <label for="sales-cashier">Cashier</label>
                    <select id="sales-cashier" name="user_id" class="sales-control">
                        <option value="">All cashiers</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}"
                                @selected((string) request('user_id') === (string) $cashier->id)>
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sales-field">
                    <label for="sales-method">Payment method</label>
                    <select id="sales-method" name="method" class="sales-control">
                        <option value="">All methods</option>
                        <option value="cash" @selected(request('method') === 'cash')>
                            Cash
                        </option>
                        <option value="mpesa" @selected(request('method') === 'mpesa')>
                            M-Pesa
                        </option>
                    </select>
                </div>

            </div>

            <div class="sales-filter-bottom">

                <div class="sales-field sales-search-field">
                    <label for="sales-search">Search transactions</label>
                    <input id="sales-search"
                           type="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Receipt number, customer or phone..."
                           class="sales-control">
                </div>

                <div class="sales-filter-actions">
                    <button type="submit" class="sales-btn sales-btn-primary">
                        <span>⌕</span> Apply filters
                    </button>

                    @if($filtersActive)
                        <a href="{{ route('sales.index') }}" class="sales-btn">
                            Reset filters
                        </a>
                    @endif
                </div>

            </div>
        </form>
    </div>

    {{-- TRANSACTIONS TABLE --}}
    <div class="sales-panel">

        <div class="sales-panel-header">
            <div>
                <div class="sales-panel-title">Sales transactions</div>
                <div class="sales-panel-subtitle">
                    Review and manage recorded sales.
                </div>
            </div>

            <span class="sales-panel-subtitle">
                {{ number_format($orders->total()) }} records
            </span>
        </div>

        <div class="sales-mobile-hint">
            Scroll horizontally to view all transaction details.
        </div>

        <div class="sales-table-wrap">
            <table class="sales-table">

                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date & Time</th>
                        <th>Cashier</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($orders as $order)

                        @php
                            $status = strtolower($order->status ?? 'pending');
                            $statusClass = $statusStyles[$status] ?? 'pending';
                        @endphp

                        <tr>

                            <td>
                                <a href="{{ route('sales.show', $order) }}"
                                   class="sales-receipt">
                                    {{ $order->receipt_no }}
                                </a>
                                <div class="sales-secondary-text">
                                    Transaction
                                </div>
                            </td>

                            <td>
                                <div class="sales-primary-text">
                                    {{ $order->created_at?->format('d M Y') ?? '-' }}
                                </div>
                                <div class="sales-secondary-text">
                                    {{ $order->created_at?->format('H:i') ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <div class="sales-primary-text">
                                    {{ $order->user->name ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <div class="sales-primary-text">
                                    {{ $order->customer->name ?? 'Walk-in Customer' }}
                                </div>
                            </td>

                            <td>
                                <span class="sales-status {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            <td>
                                <div class="sales-primary-text">
                                    {{ number_format((float) $order->total, 2) }}
                                </div>
                            </td>

                            <td>
                                <div class="sales-actions">

                                    <a href="{{ route('sales.show', $order) }}"
                                       class="sales-action">
                                        View
                                    </a>

                                    @if($order->status === 'paid')
                                        <a href="{{ route('receipts.show', $order) }}"
                                           class="sales-action">
                                            Receipt
                                        </a>
                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7">
                                <div class="sales-empty">
                                    <div class="sales-empty-icon">⌕</div>
                                    <strong>No sales found</strong>
                                    <p>
                                        No transactions match your current filters.
                                        Try adjusting your search or date range.
                                    </p>

                                    @if($filtersActive)
                                        <a href="{{ route('sales.index') }}"
                                           class="sales-btn">
                                            Clear all filters
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        @if($orders->hasPages())
            <div class="sales-pagination">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
