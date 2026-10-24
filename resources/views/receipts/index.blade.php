
@extends('layouts.app')

@section('title', 'Receipts')
@section('page-heading', 'Receipts Management')

@section('content')

@php
    $receipts = $receipts ?? collect();
    $filters = [
        'from' => request('from'),
        'to' => request('to'),
        'method' => request('method'),
        'search' => request('search'),
    ];
@endphp

<style>
    .receipts-page {
        --r-bg: #f5f8f6;
        --r-card: #ffffff;
        --r-text: #17251d;
        --r-muted: #718078;
        --r-border: #e4ebe6;
        --r-soft: #eff7f1;
        --r-green: #15803d;
        --r-green-dark: #166534;
        color: var(--r-text);
    }

    [data-theme="dark"] .receipts-page,
    .dark .receipts-page {
        --r-bg: #101a14;
        --r-card: #17231b;
        --r-text: #e5eee8;
        --r-muted: #9aaba0;
        --r-border: #2a3a30;
        --r-soft: #1d3325;
        --r-green: #4ade80;
        --r-green-dark: #22c55e;
    }

    .receipts-page * {
        box-sizing: border-box;
    }

    .receipts-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 22px;
    }

    .receipts-heading h2 {
        margin: 0;
        font-size: 21px;
        font-weight: 750;
        letter-spacing: -.5px;
        color: var(--r-text);
    }

    .receipts-heading p {
        margin: 5px 0 0;
        color: var(--r-muted);
        font-size: 13px;
    }

    .receipts-count {
        background: var(--r-soft);
        color: var(--r-green);
        border: 1px solid var(--r-border);
        padding: 8px 13px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
    }

    .receipts-card {
        background: var(--r-card);
        border: 1px solid var(--r-border);
        border-radius: 15px;
        overflow: hidden;
    }

    .receipts-filters {
        padding: 18px;
        border-bottom: 1px solid var(--r-border);
        background: var(--r-card);
    }

    .receipts-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(130px, 1fr)) auto auto;
        gap: 11px;
        align-items: end;
    }

    .receipts-field label {
        display: block;
        margin-bottom: 7px;
        font-size: 11px;
        font-weight: 700;
        color: var(--r-muted);
    }

    .receipts-field input,
    .receipts-field select {
        width: 100%;
        height: 41px;
        padding: 0 11px;
        border: 1px solid var(--r-border);
        border-radius: 9px;
        background: var(--r-bg);
        color: var(--r-text);
        font-size: 12px;
        outline: none;
    }

    .receipts-field input:focus,
    .receipts-field select:focus {
        border-color: var(--r-green);
        box-shadow: 0 0 0 3px rgba(21, 128, 61, .09);
    }

    .receipt-btn {
        height: 41px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 7px;
        padding: 0 16px;
        border-radius: 9px;
        border: 1px solid transparent;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
        transition: .2s ease;
    }

    .receipt-btn-primary {
        background: var(--r-green);
        color: #fff;
    }

    .receipt-btn-primary:hover {
        background: var(--r-green-dark);
        color: #fff;
    }

    .receipt-btn-light {
        background: var(--r-soft);
        color: var(--r-text);
        border-color: var(--r-border);
    }

    .receipt-btn-light:hover {
        border-color: var(--r-green);
        color: var(--r-green);
    }

    .receipts-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .receipts-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 720px;
    }

    .receipts-table th {
        padding: 14px 17px;
        text-align: left;
        background: var(--r-bg);
        color: var(--r-muted);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        border-bottom: 1px solid var(--r-border);
        white-space: nowrap;
    }

    .receipts-table td {
        padding: 15px 17px;
        border-bottom: 1px solid var(--r-border);
        color: var(--r-text);
        font-size: 12px;
        vertical-align: middle;
    }

    .receipts-table tbody tr:last-child td {
        border-bottom: none;
    }

    .receipts-table tbody tr:hover {
        background: var(--r-bg);
    }

    .receipt-number {
        font-weight: 750;
        color: var(--r-green);
    }

    .receipt-customer {
        font-weight: 650;
    }

    .receipt-subtext {
        display: block;
        margin-top: 4px;
        color: var(--r-muted);
        font-size: 10px;
    }

    .receipt-total {
        font-weight: 800;
        white-space: nowrap;
    }

    .receipt-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }

    .receipt-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 10px;
        border-radius: 7px;
        background: var(--r-soft);
        color: var(--r-green);
        text-decoration: none;
        font-size: 11px;
        font-weight: 700;
        border: 1px solid var(--r-border);
        transition: .2s ease;
    }

    .receipt-action:hover {
        border-color: var(--r-green);
        color: var(--r-green-dark);
    }

    .receipts-empty {
        padding: 55px 20px !important;
        text-align: center;
    }

    .receipts-empty-icon {
        width: 48px;
        height: 48px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 12px;
        border-radius: 13px;
        background: var(--r-soft);
        color: var(--r-green);
        font-size: 21px;
    }

    .receipts-empty strong {
        display: block;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .receipts-empty span {
        color: var(--r-muted);
        font-size: 12px;
    }

    .receipts-footer {
        padding: 15px 18px;
        border-top: 1px solid var(--r-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .receipts-footer-note {
        color: var(--r-muted);
        font-size: 11px;
    }

    .receipts-pagination nav {
        display: flex;
        justify-content: flex-end;
    }

    .receipts-pagination svg {
        width: 16px;
        height: 16px;
    }

    @media (max-width: 1050px) {
        .receipts-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .receipts-header {
            align-items: flex-start;
        }

        .receipts-heading h2 {
            font-size: 18px;
        }

        .receipts-filters {
            padding: 13px;
        }

        .receipts-filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .receipts-field-search {
            grid-column: 1 / -1;
        }

        .receipts-filter-grid .receipt-btn {
            width: 100%;
        }

        .receipts-footer {
            align-items: flex-start;
        }
    }
</style>

<div class="receipts-page">

    <div class="receipts-header">
        <div class="receipts-heading">
            <h2>Receipts</h2>
            <p>View, search and download completed sale receipts.</p>
        </div>

        <div class="receipts-count">
            {{ method_exists($receipts, 'total') ? number_format($receipts->total()) : number_format($receipts->count()) }}
            Receipts
        </div>
    </div>

    <div class="receipts-card">

        {{-- Filters --}}
        <form method="GET" action="{{ route('receipts.index') }}" class="receipts-filters">
            <div class="receipts-filter-grid">

                <div class="receipts-field">
                    <label for="receipt-from">FROM DATE</label>
                    <input
                        type="date"
                        id="receipt-from"
                        name="from"
                        value="{{ $filters['from'] }}"
                    >
                </div>

                <div class="receipts-field">
                    <label for="receipt-to">TO DATE</label>
                    <input
                        type="date"
                        id="receipt-to"
                        name="to"
                        value="{{ $filters['to'] }}"
                    >
                </div>

                <div class="receipts-field">
                    <label for="receipt-method">PAYMENT METHOD</label>
                    <select id="receipt-method" name="method">
                        <option value="">All methods</option>
                        <option value="cash" @selected($filters['method'] === 'cash')>
                            Cash
                        </option>
                        <option value="mpesa" @selected($filters['method'] === 'mpesa')>
                            M-Pesa
                        </option>
                    </select>
                </div>

                <div class="receipts-field receipts-field-search">
                    <label for="receipt-search">SEARCH RECEIPTS</label>
                    <input
                        type="text"
                        id="receipt-search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Receipt no., customer, phone..."
                    >
                </div>

                <button type="submit" class="receipt-btn receipt-btn-primary">
                    <span>⌕</span> Filter
                </button>

                <a href="{{ route('receipts.index') }}" class="receipt-btn receipt-btn-light">
                    Clear
                </a>

            </div>
        </form>

        {{-- Receipts table --}}
        <div class="receipts-table-wrap">
            <table class="receipts-table">
                <thead>
                    <tr>
                        <th>Receipt Number</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($receipts as $r)
                        <tr>
                            <td>
                                <span class="receipt-number">
                                    {{ $r->receipt_no }}
                                </span>
                            </td>

                            <td>
                                <strong>
                                    {{ $r->created_at?->format('d M Y') ?? '-' }}
                                </strong>
                                <span class="receipt-subtext">
                                    {{ $r->created_at?->format('H:i') ?? '' }}
                                </span>
                            </td>

                            <td>
                                <span class="receipt-customer">
                                    {{ $r->customer->name ?? 'Walk-in Customer' }}
                                </span>
                                @if(!empty($r->customer?->phone))
                                    <span class="receipt-subtext">
                                        {{ $r->customer->phone }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $r->user->name ?? '-' }}
                            </td>

                            <td>
                                <span class="receipt-total">
                                    {{ number_format((float) $r->total, 2) }}
                                </span>
                            </td>

                            <td>
                                <div class="receipt-actions">
                                    <a
                                        href="{{ route('receipts.show', $r) }}"
                                        class="receipt-action"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('receipts.download', $r) }}"
                                        class="receipt-action"
                                    >
                                        Download
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="receipts-empty">
                                <div class="receipts-empty-icon">▤</div>
                                <strong>No receipts found</strong>
                                <span>
                                    No receipts match your current filters.
                                    Try changing your search or date range.
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="receipts-footer">
            <div class="receipts-footer-note">
                Showing
                {{ $receipts->count() }}
                {{ $receipts->count() === 1 ? 'receipt' : 'receipts' }}
                on this page
            </div>

            @if(method_exists($receipts, 'links'))
                <div class="receipts-pagination">
                    {{ $receipts->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@endsection
