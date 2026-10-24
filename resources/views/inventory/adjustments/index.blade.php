
@extends('layouts.app')

@section('title', 'Stock Adjustments')
@section('page-heading', 'Stock Adjustments')

@section('content')

@php
    $adjustments = $adjustments ?? collect();

    $totalAdjustments = method_exists($adjustments, 'total')
        ? $adjustments->total()
        : $adjustments->count();
@endphp

<style>
    .adjustments-page {
        --adj-bg: #ffffff;
        --adj-soft: #f5f8f6;
        --adj-text: #17251d;
        --adj-muted: #718078;
        --adj-border: #e4ebe6;
        --adj-green: #15803d;
        --adj-green-soft: #eff7f1;
        color: var(--adj-text);
    }

    [data-theme="dark"] .adjustments-page,
    .dark .adjustments-page {
        --adj-bg: #17231b;
        --adj-soft: #1d3325;
        --adj-text: #e5eee8;
        --adj-muted: #9aaba0;
        --adj-border: #2a3a30;
        --adj-green: #4ade80;
        --adj-green-soft: #1d3325;
    }

    .adjustments-page .adj-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .adjustments-page .adj-heading h2 {
        margin: 0 0 5px;
        font-size: 23px;
        font-weight: 750;
        letter-spacing: -0.5px;
        color: var(--adj-text);
    }

    .adjustments-page .adj-heading p {
        margin: 0;
        color: var(--adj-muted);
        font-size: 13px;
    }

    .adjustments-page .adj-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 15px;
        border: 1px solid var(--adj-green);
        border-radius: 10px;
        background: var(--adj-green);
        color: #fff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 650;
        transition: .2s ease;
    }

    .adjustments-page .adj-btn:hover {
        filter: brightness(.94);
        transform: translateY(-1px);
    }

    .adjustments-page .adj-summary {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 17px;
        margin-bottom: 20px;
        background: var(--adj-bg);
        border: 1px solid var(--adj-border);
        border-radius: 14px;
    }

    .adjustments-page .adj-summary-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: 12px;
        background: var(--adj-green-soft);
        color: var(--adj-green);
        font-size: 20px;
    }

    .adjustments-page .adj-summary-label {
        color: var(--adj-muted);
        font-size: 12px;
        margin-bottom: 4px;
    }

    .adjustments-page .adj-summary-value {
        color: var(--adj-text);
        font-size: 21px;
        font-weight: 750;
    }

    .adjustments-page .adj-alert {
        padding: 12px 15px;
        margin-bottom: 18px;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
    }

    .adjustments-page .adj-table-card {
        overflow: hidden;
        background: var(--adj-bg);
        border: 1px solid var(--adj-border);
        border-radius: 14px;
    }

    .adjustments-page .adj-table-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 17px 19px;
        border-bottom: 1px solid var(--adj-border);
    }

    .adjustments-page .adj-table-top h3 {
        margin: 0;
        color: var(--adj-text);
        font-size: 14px;
        font-weight: 700;
    }

    .adjustments-page .adj-table-top span {
        color: var(--adj-muted);
        font-size: 12px;
    }

    .adjustments-page .adj-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .adjustments-page table {
        width: 100%;
        min-width: 780px;
        border-collapse: collapse;
    }

    .adjustments-page th {
        padding: 13px 16px;
        background: var(--adj-soft);
        color: var(--adj-muted);
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .45px;
        white-space: nowrap;
    }

    .adjustments-page td {
        padding: 15px 16px;
        border-top: 1px solid var(--adj-border);
        color: var(--adj-text);
        font-size: 13px;
        vertical-align: middle;
    }

    .adjustments-page tbody tr {
        transition: background .15s ease;
    }

    .adjustments-page tbody tr:hover {
        background: var(--adj-soft);
    }

    .adjustments-page .adj-date {
        white-space: nowrap;
        color: var(--adj-muted);
        font-size: 12px;
    }

    .adjustments-page .adj-product {
        font-weight: 650;
    }

    .adjustments-page .adj-change {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        padding: 5px 9px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 750;
    }

    .adjustments-page .adj-positive {
        background: #dcfce7;
        color: #166534;
    }

    .adjustments-page .adj-negative {
        background: #fee2e2;
        color: #b91c1c;
    }

    .adjustments-page .adj-neutral {
        background: var(--adj-soft);
        color: var(--adj-muted);
    }

    .adjustments-page .adj-stock {
        font-weight: 650;
        white-space: nowrap;
    }

    .adjustments-page .adj-reason {
        max-width: 260px;
        line-height: 1.5;
    }

    .adjustments-page .adj-user {
        display: flex;
        align-items: center;
        gap: 9px;
        white-space: nowrap;
    }

    .adjustments-page .adj-avatar {
        width: 29px;
        height: 29px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--adj-green-soft);
        color: var(--adj-green);
        font-size: 11px;
        font-weight: 750;
    }

    .adjustments-page .adj-empty {
        padding: 55px 20px;
        text-align: center;
    }

    .adjustments-page .adj-empty-icon {
        width: 52px;
        height: 52px;
        display: grid;
        place-items: center;
        margin: 0 auto 13px;
        border-radius: 15px;
        background: var(--adj-soft);
        color: var(--adj-muted);
        font-size: 23px;
    }

    .adjustments-page .adj-empty h4 {
        margin: 0 0 6px;
        color: var(--adj-text);
        font-size: 14px;
    }

    .adjustments-page .adj-empty p {
        margin: 0;
        color: var(--adj-muted);
        font-size: 12px;
    }

    .adjustments-page .adj-pagination {
        padding: 15px 18px;
        border-top: 1px solid var(--adj-border);
    }

    .adjustments-page .adj-pagination nav {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    @media (max-width: 640px) {
        .adjustments-page .adj-heading h2 {
            font-size: 20px;
        }

        .adjustments-page .adj-header {
            align-items: flex-start;
        }

        .adjustments-page .adj-btn {
            width: 100%;
        }

        .adjustments-page .adj-table-top {
            padding: 14px;
        }
    }
</style>

<div class="adjustments-page">

    <div class="adj-header">
        <div class="adj-heading">
            <h2>Stock Adjustments</h2>
            <p>Track inventory changes and review adjustment history.</p>
        </div>

        <a href="{{ route('adjustments.create') }}" class="adj-btn">
            <span>+</span>
            New adjustment
        </a>
    </div>

    @if(session('status'))
        <div class="adj-alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="adj-summary">
        <div class="adj-summary-icon">↕</div>
        <div>
            <div class="adj-summary-label">Total stock adjustments</div>
            <div class="adj-summary-value">{{ number_format($totalAdjustments) }}</div>
        </div>
    </div>

    <div class="adj-table-card">

        <div class="adj-table-top">
            <h3>Adjustment history</h3>
            <span>{{ number_format($adjustments->count()) }} records on this page</span>
        </div>

        <div class="adj-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Change</th>
                        <th>Before → After</th>
                        <th>Reason / Notes</th>
                        <th>Adjusted by</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($adjustments as $a)
                        @php
                            $change = (int) $a->change;
                            $userName = $a->user->name ?? 'Unknown';
                            $initials = collect(explode(' ', trim($userName)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                ->implode('');
                        @endphp

                        <tr>
                            <td>
                                <div class="adj-date">
                                    {{ $a->created_at?->format('d M Y') ?? '—' }}
                                    <br>
                                    {{ $a->created_at?->format('H:i') ?? '' }}
                                </div>
                            </td>

                            <td>
                                <span class="adj-product">
                                    {{ $a->product->name ?? 'Deleted product' }}
                                </span>
                            </td>

                            <td>
                                <span class="adj-change
                                    {{ $change > 0 ? 'adj-positive' : ($change < 0 ? 'adj-negative' : 'adj-neutral') }}">
                                    {{ $change > 0 ? '+' : '' }}{{ number_format($change) }}
                                </span>
                            </td>

                            <td>
                                <span class="adj-stock">
                                    {{ number_format($a->stock_before) }}
                                    →
                                    {{ number_format($a->stock_after) }}
                                </span>
                            </td>

                            <td>
                                <div class="adj-reason">
                                    <strong>{{ ucfirst($a->reason ?? 'Other') }}</strong>
                                    @if($a->notes)
                                        <div style="color:var(--adj-muted); margin-top:3px;">
                                            {{ $a->notes }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="adj-user">
                                    <span class="adj-avatar">{{ $initials ?: '?' }}</span>
                                    <span>{{ $userName }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="adj-empty">
                                    <div class="adj-empty-icon">↕</div>
                                    <h4>No stock adjustments yet</h4>
                                    <p>Your inventory adjustment history will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($adjustments, 'links'))
            <div class="adj-pagination">
                {{ $adjustments->links() }}
            </div>
        @endif

    </div>
</div>

@endsection
