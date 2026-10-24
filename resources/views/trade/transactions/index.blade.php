
@extends('layouts.app')

@section('title', 'Transactions')
@section('page-heading', 'Transactions Management')

@section('content')

@php
    $totals = $totals ?? collect();
    $payments = $payments ?? collect();

    $filters = [
        'from' => request('from'),
        'to' => request('to'),
        'method' => request('method'),
        'status' => request('status'),
        'search' => request('search'),
    ];

    $statusStyles = [
        'success' => 'success',
        'paid' => 'success',
        'pending' => 'pending',
        'failed' => 'failed',
        'cancelled' => 'cancelled',
    ];

    $totalTransactions = $totals->sum('count');
    $totalAmount = $totals->sum('amount');
@endphp

<style>
    .transactions-page {
        --t-bg: #f5f8f6;
        --t-card: #ffffff;
        --t-text: #17251d;
        --t-muted: #718078;
        --t-border: #e4ebe6;
        --t-soft: #eff7f1;
        --t-green: #15803d;
        --t-green-dark: #166534;
        color: var(--t-text);
    }

    [data-theme="dark"] .transactions-page,
    .dark .transactions-page {
        --t-bg: #101a14;
        --t-card: #17231b;
        --t-text: #e5eee8;
        --t-muted: #9aaba0;
        --t-border: #2a3a30;
        --t-soft: #1d3325;
        --t-green: #4ade80;
        --t-green-dark: #22c55e;
    }

    .transactions-page * {
        box-sizing: border-box;
    }

    .transactions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 20px;
    }

    .transactions-heading h2 {
        margin: 0;
        font-size: 21px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .transactions-heading p {
        margin: 5px 0 0;
        font-size: 12px;
        color: var(--t-muted);
    }

    .transactions-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .transaction-stat {
        padding: 17px;
        background: var(--t-card);
        border: 1px solid var(--t-border);
        border-radius: 14px;
    }

    .transaction-stat-label {
        color: var(--t-muted);
        font-size: 11px;
        font-weight: 650;
    }

    .transaction-stat-value {
        display: block;
        margin-top: 9px;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -.5px;
        overflow-wrap: anywhere;
    }

    .transaction-stat-note {
        display: block;
        margin-top: 5px;
        color: var(--t-muted);
        font-size: 10px;
    }

    .transaction-card {
        background: var(--t-card);
        border: 1px solid var(--t-border);
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 18px;
    }

    .transaction-card-header {
        padding: 17px 18px;
        border-bottom: 1px solid var(--t-border);
    }

    .transaction-card-header h3 {
        margin: 0;
        font-size: 14px;
        font-weight: 750;
    }

    .transaction-card-header p {
        margin: 5px 0 0;
        color: var(--t-muted);
        font-size: 11px;
    }

    .transaction-summary-table,
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
    }

    .transaction-summary-table th,
    .transaction-table th {
        padding: 13px 16px;
        background: var(--t-bg);
        border-bottom: 1px solid var(--t-border);
        color: var(--t-muted);
        text-align: left;
        text-transform: uppercase;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .5px;
        white-space: nowrap;
    }

    .transaction-summary-table td {
        padding: 13px 16px;
        border-bottom: 1px solid var(--t-border);
        font-size: 12px;
    }

    .transaction-summary-table tr:last-child td {
        border-bottom: none;
    }

    .transaction-summary-table .summary-total {
        font-weight: 800;
    }

    .transaction-filters {
        padding: 17px 18px;
        border-bottom: 1px solid var(--t-border);
    }

    .transaction-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(120px, 1fr));
        gap: 11px;
        align-items: end;
    }

    .transaction-field label {
        display: block;
        margin-bottom: 7px;
        color: var(--t-muted);
        font-size: 10px;
        font-weight: 750;
    }

    .transaction-field input,
    .transaction-field select {
        width: 100%;
        height: 40px;
        padding: 0 10px;
        background: var(--t-bg);
        color: var(--t-text);
        border: 1px solid var(--t-border);
        border-radius: 9px;
        outline: none;
        font-size: 11px;
    }

    .transaction-field input:focus,
    .transaction-field select:focus {
        border-color: var(--t-green);
        box-shadow: 0 0 0 3px rgba(21, 128, 61, .08);
    }

    .transaction-search {
        grid-column: span 2;
    }

    .transaction-buttons {
        display: flex;
        gap: 8px;
        grid-column: span 3;
    }

    .transaction-btn {
        min-height: 40px;
        padding: 0 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid var(--t-border);
        border-radius: 9px;
        background: var(--t-card);
        color: var(--t-text);
        text-decoration: none;
        font-size: 11px;
        font-weight: 750;
        cursor: pointer;
        white-space: nowrap;
        transition: .2s ease;
    }

    .transaction-btn:hover {
        border-color: var(--t-green);
        color: var(--t-green);
    }

    .transaction-btn-primary {
        background: var(--t-green);
        border-color: var(--t-green);
        color: #fff;
    }

    .transaction-btn-primary:hover {
        background: var(--t-green-dark);
        color: #fff;
    }

    .transaction-export {
        margin-left: auto;
    }

    .transaction-table-wrap {
        overflow-x: auto;
        width: 100%;
    }

    .transaction-table {
        min-width: 1050px;
    }

    .transaction-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--t-border);
        font-size: 11px;
        vertical-align: middle;
    }

    .transaction-table tbody tr:last-child td {
        border-bottom: none;
    }

    .transaction-table tbody tr:hover {
        background: var(--t-bg);
    }

    .transaction-date {
        font-weight: 700;
        white-space: nowrap;
    }

    .transaction-muted {
        display: block;
        margin-top: 4px;
        color: var(--t-muted);
        font-size: 10px;
    }

    .transaction-receipt {
        color: var(--t-green);
        font-weight: 750;
        text-decoration: none;
    }

    .transaction-receipt:hover {
        text-decoration: underline;
    }

    .transaction-method {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 7px;
        background: var(--t-soft);
        color: var(--t-green);
        font-size: 10px;
        font-weight: 750;
        white-space: nowrap;
    }

    .transaction-amount {
        font-weight: 800;
        white-space: nowrap;
    }

    .transaction-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 750;
        white-space: nowrap;
    }

    .transaction-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-success {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-cancelled {
        background: #f1f5f9;
        color: #475569;
    }

    .transaction-reference {
        max-width: 170px;
        overflow-wrap: anywhere;
        color: var(--t-muted);
        font-family: monospace;
        font-size: 10px;
    }

    .transaction-empty {
        padding: 45px 15px !important;
        text-align: center;
        color: var(--t-muted);
    }

    .transaction-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 15px 18px;
        border-top: 1px solid var(--t-border);
    }

    .transaction-footer-note {
        color: var(--t-muted);
        font-size: 11px;
    }

    .transaction-pagination nav {
        display: flex;
        justify-content: flex-end;
    }

    .transaction-pagination svg {
        width: 16px;
        height: 16px;
    }

    @media(max-width: 1100px) {
        .transaction-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .transaction-search,
        .transaction-buttons {
            grid-column: span 3;
        }
    }

    @media(max-width: 650px) {
        .transactions-summary {
            grid-template-columns: 1fr;
            gap: 9px;
        }

        .transaction-stat {
            padding: 14px;
        }

        .transaction-stat-value {
            font-size: 19px;
        }

        .transactions-heading h2 {
            font-size: 18px;
        }

        .transaction-filters {
            padding: 13px;
        }

        .transaction-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .transaction-search,
        .transaction-buttons {
            grid-column: span 2;
        }

        .transaction-buttons {
            flex-wrap: wrap;
        }

        .transaction-buttons .transaction-btn {
            flex: 1;
        }

        .transaction-card-header {
            padding: 14px;
        }

        .transaction-footer {
            padding: 13px;
        }
    }
</style>

<div class="transactions-page">

    {{-- Page heading --}}
    <div class="transactions-header">
        <div class="transactions-heading">
            <h2>Transactions</h2>
            <p>Monitor payment activity, transaction statuses and collections.</p>
        </div>
    </div>

    {{-- Transaction summary --}}
    <div class="transactions-summary">

        <div class="transaction-stat">
            <span class="transaction-stat-label">Total Transactions</span>
            <strong class="transaction-stat-value">
                {{ number_format($totalTransactions) }}
            </strong>
            <span class="transaction-stat-note">Across the selected filters</span>
        </div>

        <div class="transaction-stat">
            <span class="transaction-stat-label">Transaction Amount</span>
            <strong class="transaction-stat-value">
                {{ number_format((float) $totalAmount, 2) }}
            </strong>
            <span class="transaction-stat-note">Combined amount in the summary</span>
        </div>

        <div class="transaction-stat">
            <span class="transaction-stat-label">Records on This Page</span>
            <strong class="transaction-stat-value">
                {{ number_format($payments->count()) }}
            </strong>
            <span class="transaction-stat-note">Currently displayed transactions</span>
        </div>

    </div>

    {{-- Status summary --}}
    <div class="transaction-card">

        <div class="transaction-card-header">
            <h3>Transaction Summary</h3>
            <p>Breakdown by payment status.</p>
        </div>

        <div class="transaction-table-wrap">
            <table class="transaction-summary-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Transaction Count</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($totals as $status => $t)
                        <tr>
                            <td>
                                <span class="transaction-status status-{{ $statusStyles[strtolower($status)] ?? 'cancelled' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            <td>{{ number_format((int) $t->count) }}</td>

                            <td class="summary-total">
                                {{ number_format((float) $t->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="transaction-empty">
                                No transaction summary available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- Transactions listing --}}
    <div class="transaction-card">

        <div class="transaction-card-header">
            <h3>Payment Transactions</h3>
            <p>Search, filter and export your payment records.</p>
        </div>

        {{-- Filters --}}
        <form
            method="GET"
            action="{{ route('transactions.index') }}"
            class="transaction-filters"
        >
            <div class="transaction-filter-grid">

                <div class="transaction-field">
                    <label for="transaction-from">FROM DATE</label>
                    <input
                        type="date"
                        id="transaction-from"
                        name="from"
                        value="{{ $filters['from'] }}"
                    >
                </div>

                <div class="transaction-field">
                    <label for="transaction-to">TO DATE</label>
                    <input
                        type="date"
                        id="transaction-to"
                        name="to"
                        value="{{ $filters['to'] }}"
                    >
                </div>

                <div class="transaction-field">
                    <label for="transaction-method">PAYMENT METHOD</label>
                    <select id="transaction-method" name="method">
                        <option value="">All methods</option>
                        <option value="cash" @selected($filters['method'] === 'cash')>
                            Cash
                        </option>
                        <option value="mpesa" @selected($filters['method'] === 'mpesa')>
                            M-Pesa
                        </option>
                    </select>
                </div>

                <div class="transaction-field">
                    <label for="transaction-status">PAYMENT STATUS</label>
                    <select id="transaction-status" name="status">
                        <option value="">All statuses</option>

                        @foreach(['pending', 'success', 'failed', 'cancelled'] as $s)
                            <option
                                value="{{ $s }}"
                                @selected($filters['status'] === $s)
                            >
                                {{ ucfirst($s) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="transaction-field transaction-search">
                    <label for="transaction-search">SEARCH TRANSACTIONS</label>
                    <input
                        type="text"
                        id="transaction-search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Phone number or reference..."
                    >
                </div>

                <div class="transaction-buttons">
                    <button type="submit" class="transaction-btn transaction-btn-primary">
                        Filter Transactions
                    </button>

                    <a
                        href="{{ route('transactions.index') }}"
                        class="transaction-btn"
                    >
                        Clear
                    </a>

                    <a
                        href="{{ route('transactions.export', request()->query()) }}"
                        class="transaction-btn transaction-export"
                    >
                        Export CSV
                    </a>
                </div>

            </div>
        </form>

        {{-- Transactions table --}}
        <div class="transaction-table-wrap">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt</th>
                        <th>Method</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Reference</th>
                        <th>Paid At</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($payments as $p)
                        @php
                            $paymentStatus = strtolower($p->status ?? '');
                            $statusClass = $statusStyles[$paymentStatus] ?? 'cancelled';
                        @endphp

                        <tr>
                            <td>
                                <span class="transaction-date">
                                    {{ $p->created_at?->format('d M Y') ?? '-' }}
                                </span>

                                <span class="transaction-muted">
                                    {{ $p->created_at?->format('H:i') ?? '' }}
                                </span>
                            </td>

                            <td>
                                @if($p->order)
                                    <a
                                        href="{{ route('sales.show', $p->order) }}"
                                        class="transaction-receipt"
                                    >
                                        {{ $p->order->receipt_no }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                <span class="transaction-method">
                                    {{ ucfirst($p->method ?? '-') }}
                                </span>
                            </td>

                            <td>
                                {{ $p->phone ?? '-' }}
                            </td>

                            <td>
                                <span class="transaction-amount">
                                    {{ number_format((float) $p->amount, 2) }}
                                </span>
                            </td>

                            <td>
                                <span class="transaction-status status-{{ $statusClass }}">
                                    {{ ucfirst($p->status ?? 'Unknown') }}
                                </span>
                            </td>

                            <td>
                                <span class="transaction-reference">
                                    {{ $p->provider_reference ?? $p->external_reference ?? '-' }}
                                </span>
                            </td>

                            <td>
                                @if($p->paid_at)
                                    <span class="transaction-date">
                                        {{ $p->paid_at->format('d M Y') }}
                                    </span>
                                    <span class="transaction-muted">
                                        {{ $p->paid_at->format('H:i') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8" class="transaction-empty">
                                No transactions found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="transaction-footer">
            <div class="transaction-footer-note">
                Showing {{ $payments->count() }} transaction records on this page
            </div>

            @if(method_exists($payments, 'links'))
                <div class="transaction-pagination">
                    {{ $payments->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>

</div>

@endsection
