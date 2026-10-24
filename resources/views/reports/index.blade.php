
@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('page-heading', 'Reports & Analytics')

@section('content')

@php
    $summary = $summary ?? (object) [
        'orders' => 0,
        'revenue' => 0,
        'average' => 0,
    ];

    $byProduct = $byProduct ?? collect();
    $byCashier = $byCashier ?? collect();
    $byPayment = $byPayment ?? collect();
    $lowStock = $lowStock ?? collect();

    $currency = $currency ?? 'KES';

    $orders = (int) ($summary->orders ?? 0);
    $revenue = (float) ($summary->revenue ?? 0);
    $average = (float) ($summary->average ?? 0);

    $productRevenue = $byProduct->sum('revenue');
    $paymentTotal = $byPayment->sum('total');
    $cashierRevenue = $byCashier->sum('revenue');

    $formatMoney = fn($amount) => number_format((float) $amount, 2);
@endphp

<style>
    .reports-page {
        --rp-bg: #f5f8f6;
        --rp-card: #ffffff;
        --rp-text: #17251d;
        --rp-muted: #718078;
        --rp-border: #e4ebe6;
        --rp-soft: #eff7f1;
        --rp-green: #15803d;
        --rp-green-dark: #166534;
        --rp-red: #dc2626;
        --rp-orange: #d97706;

        color: var(--rp-text);
    }

    [data-theme="dark"] .reports-page,
    .dark .reports-page {
        --rp-bg: #101a14;
        --rp-card: #17231b;
        --rp-text: #e5eee8;
        --rp-muted: #9aaba0;
        --rp-border: #2a3a30;
        --rp-soft: #1d3325;
        --rp-green: #4ade80;
        --rp-green-dark: #22c55e;
        --rp-red: #f87171;
        --rp-orange: #fbbf24;
    }

    .reports-page * {
        box-sizing: border-box;
    }

    .rp-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    .rp-title {
        margin: 0;
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.6px;
    }

    .rp-subtitle {
        color: var(--rp-muted);
        font-size: 13px;
        margin: 5px 0 0;
    }

    .rp-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
    }

    .rp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 39px;
        padding: 9px 14px;
        border-radius: 9px;
        border: 1px solid var(--rp-border);
        background: var(--rp-card);
        color: var(--rp-text);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: .2s;
    }

    .rp-btn:hover {
        background: var(--rp-soft);
        border-color: var(--rp-green);
    }

    .rp-btn-primary {
        background: var(--rp-green);
        border-color: var(--rp-green);
        color: #fff;
    }

    .rp-btn-primary:hover {
        background: var(--rp-green-dark);
        color: #fff;
    }

    .rp-filter {
        background: var(--rp-card);
        border: 1px solid var(--rp-border);
        border-radius: 14px;
        padding: 17px;
        margin-bottom: 20px;
    }

    .rp-filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr)) auto auto;
        gap: 12px;
        align-items: end;
    }

    .rp-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 750;
        color: var(--rp-muted);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .rp-field input {
        width: 100%;
        height: 41px;
        padding: 9px 11px;
        background: var(--rp-card);
        color: var(--rp-text);
        border: 1px solid var(--rp-border);
        border-radius: 9px;
        font-size: 13px;
        outline: none;
    }

    .rp-field input:focus {
        border-color: var(--rp-green);
        box-shadow: 0 0 0 3px rgba(21,128,61,.1);
    }

    .rp-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin: 24px 0 13px;
    }

    .rp-section-heading h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
    }

    .rp-section-heading p {
        color: var(--rp-muted);
        font-size: 11px;
        margin: 4px 0 0;
    }

    .rp-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .rp-kpi {
        background: var(--rp-card);
        border: 1px solid var(--rp-border);
        border-radius: 14px;
        padding: 17px;
        min-width: 0;
    }

    .rp-kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .rp-kpi-label {
        color: var(--rp-muted);
        font-size: 12px;
        font-weight: 650;
    }

    .rp-kpi-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: var(--rp-soft);
        color: var(--rp-green);
        font-size: 17px;
    }

    .rp-kpi-value {
        margin-top: 13px;
        font-size: clamp(19px, 2vw, 25px);
        font-weight: 800;
        letter-spacing: -.7px;
        overflow-wrap: anywhere;
    }

    .rp-kpi-note {
        margin-top: 5px;
        font-size: 11px;
        color: var(--rp-muted);
    }

    .rp-grid-two {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .rp-card {
        background: var(--rp-card);
        border: 1px solid var(--rp-border);
        border-radius: 14px;
        overflow: hidden;
        min-width: 0;
        margin-bottom: 16px;
    }

    .rp-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--rp-border);
    }

    .rp-card-header h3 {
        font-size: 14px;
        font-weight: 800;
        margin: 0;
    }

    .rp-card-header span {
        font-size: 11px;
        color: var(--rp-muted);
    }

    .rp-card-body {
        padding: 17px 18px;
    }

    .rp-table-wrap {
        overflow-x: auto;
    }

    .rp-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 520px;
    }

    .rp-table th {
        background: var(--rp-bg);
        color: var(--rp-muted);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 12px 15px;
        text-align: left;
        white-space: nowrap;
        border-bottom: 1px solid var(--rp-border);
    }

    .rp-table td {
        padding: 13px 15px;
        font-size: 12px;
        border-bottom: 1px solid var(--rp-border);
        vertical-align: middle;
    }

    .rp-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .rp-table tbody tr:hover {
        background: var(--rp-bg);
    }

    .rp-money {
        font-weight: 750;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .rp-name {
        font-weight: 700;
    }

    .rp-muted {
        color: var(--rp-muted);
    }

    .rp-rank {
        width: 25px;
        height: 25px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        border-radius: 7px;
        background: var(--rp-soft);
        color: var(--rp-green);
        font-size: 11px;
        font-weight: 800;
    }

    .rp-progress {
        height: 6px;
        background: var(--rp-bg);
        border-radius: 20px;
        overflow: hidden;
        margin-top: 7px;
        min-width: 70px;
    }

    .rp-progress span {
        display: block;
        height: 100%;
        background: var(--rp-green);
        border-radius: inherit;
    }

    .rp-method-list {
        display: flex;
        flex-direction: column;
        gap: 17px;
    }

    .rp-method-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 12px;
    }

    .rp-method-name {
        font-weight: 750;
    }

    .rp-method-amount {
        font-weight: 800;
        white-space: nowrap;
    }

    .rp-method-track {
        height: 8px;
        background: var(--rp-bg);
        border-radius: 20px;
        overflow: hidden;
        margin-top: 8px;
    }

    .rp-method-track span {
        display: block;
        height: 100%;
        background: var(--rp-green);
        border-radius: inherit;
    }

    .rp-method-meta {
        margin-top: 5px;
        color: var(--rp-muted);
        font-size: 10px;
    }

    .rp-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 20px;
        background: var(--rp-soft);
        color: var(--rp-green);
        font-size: 10px;
        font-weight: 800;
    }

    .rp-badge-warning {
        background: #fff7ed;
        color: #c2410c;
    }

    [data-theme="dark"] .rp-badge-warning,
    .dark .rp-badge-warning {
        background: #422b18;
        color: #fdba74;
    }

    .rp-badge-danger {
        background: #fef2f2;
        color: #b91c1c;
    }

    [data-theme="dark"] .rp-badge-danger,
    .dark .rp-badge-danger {
        background: #451d1d;
        color: #fca5a5;
    }

    .rp-empty {
        padding: 25px 15px !important;
        text-align: center;
        color: var(--rp-muted);
    }

    .rp-empty-icon {
        font-size: 22px;
        margin-bottom: 6px;
    }

    .rp-summary-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        border-top: 1px solid var(--rp-border);
        padding: 13px 18px;
        color: var(--rp-muted);
        font-size: 11px;
    }

    .rp-summary-footer strong {
        color: var(--rp-text);
    }

    .rp-print-only {
        display: none;
    }

    @media(max-width: 1000px) {
        .rp-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media(max-width: 700px) {
        .rp-grid-two {
            grid-template-columns: 1fr;
        }

        .rp-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .rp-filter-grid .rp-btn {
            width: 100%;
        }
    }

    @media(max-width: 480px) {
        .rp-header {
            align-items: flex-start;
        }

        .rp-actions {
            width: 100%;
        }

        .rp-actions .rp-btn {
            flex: 1;
        }

        .rp-filter-grid {
            grid-template-columns: 1fr;
        }

        .rp-kpis {
            gap: 9px;
        }

        .rp-kpi {
            padding: 13px;
        }

        .rp-kpi-value {
            font-size: 18px;
        }
    }

    @media print {
        body {
            background: #fff !important;
        }

        .reports-page {
            color: #111 !important;
        }

        .rp-filter,
        .rp-actions,
        .rp-no-print {
            display: none !important;
        }

        .rp-card,
        .rp-kpi {
            break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }

        .rp-print-only {
            display: block;
            margin-bottom: 15px;
        }

        .rp-table th,
        .rp-table td {
            color: #111 !important;
        }
    }
</style>

<div class="reports-page">

    <div class="rp-header">
        <div>
            <h2 class="rp-title">Reports & Analytics</h2>
            <p class="rp-subtitle">
                Monitor sales, products, cashiers, payments and inventory.
            </p>
        </div>

        <div class="rp-actions">
            <button type="button" class="rp-btn" onclick="window.print()">
                <span>⎙</span> Print Report
            </button>

            <button type="button" class="rp-btn rp-btn-primary" onclick="exportAllReports()">
                <span>↓</span> Export CSV
            </button>
        </div>
    </div>

    <div class="rp-print-only">
        <h2>Business Performance Report</h2>
        <p>
            Period: {{ $from->toDateString() }} to {{ $to->toDateString() }}
        </p>
    </div>

    {{-- Date filters --}}
    <form method="GET" class="rp-filter">
        <div class="rp-filter-grid">

            <div class="rp-field">
                <label for="from">From Date</label>
                <input
                    type="date"
                    id="from"
                    name="from"
                    value="{{ $from->toDateString() }}"
                    required
                >
            </div>

            <div class="rp-field">
                <label for="to">To Date</label>
                <input
                    type="date"
                    id="to"
                    name="to"
                    value="{{ $to->toDateString() }}"
                    required
                >
            </div>

            <button type="submit" class="rp-btn rp-btn-primary">
                Apply Filters
            </button>

            <a href="{{ url()->current() }}" class="rp-btn">
                Reset
            </a>

        </div>
    </form>

    {{-- KPI cards --}}
    <div class="rp-section-heading">
        <div>
            <h3>Sales Overview</h3>
            <p>Performance for the selected reporting period.</p>
        </div>

        <span class="rp-badge">
            {{ $from->toDateString() }} — {{ $to->toDateString() }}
        </span>
    </div>

    <div class="rp-kpis">

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Paid Orders</span>
                <span class="rp-kpi-icon">▤</span>
            </div>
            <div class="rp-kpi-value">{{ number_format($orders) }}</div>
            <div class="rp-kpi-note">Successfully paid orders</div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Total Revenue</span>
                <span class="rp-kpi-icon">↗</span>
            </div>
            <div class="rp-kpi-value">{{ $currency }} {{ $formatMoney($revenue) }}</div>
            <div class="rp-kpi-note">Revenue from paid orders</div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Average Sale</span>
                <span class="rp-kpi-icon">◉</span>
            </div>
            <div class="rp-kpi-value">{{ $currency }} {{ $formatMoney($average) }}</div>
            <div class="rp-kpi-note">Average value per paid order</div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Low Stock Items</span>
                <span class="rp-kpi-icon">⚠</span>
            </div>
            <div class="rp-kpi-value">{{ number_format($lowStock->count()) }}</div>
            <div class="rp-kpi-note">Products requiring stock attention</div>
        </div>

    </div>

    {{-- Product and cashier reports --}}
    <div class="rp-section-heading">
        <div>
            <h3>Sales Performance</h3>
            <p>Understand which products and team members generate sales.</p>
        </div>
    </div>

    <div class="rp-grid-two">

        {{-- Top products --}}
        <div class="rp-card">
            <div class="rp-card-header">
                <h3>Top Products</h3>
                <span>{{ $byProduct->count() }} products</span>
            </div>

            <div class="rp-table-wrap">
                <table class="rp-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Qty Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($byProduct as $row)
                            <tr>
                                <td>
                                    <span class="rp-rank">{{ $loop->iteration }}</span>
                                </td>
                                <td class="rp-name">{{ $row->name }}</td>
                                <td>{{ number_format($row->qty) }}</td>
                                <td class="rp-money">
                                    {{ $currency }} {{ $formatMoney($row->revenue) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="rp-empty">
                                    <div class="rp-empty-icon">▤</div>
                                    No product sales in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rp-summary-footer">
                <span>Total product revenue</span>
                <strong>{{ $currency }} {{ $formatMoney($productRevenue) }}</strong>
            </div>
        </div>

        {{-- Cashier performance --}}
        <div class="rp-card">
            <div class="rp-card-header">
                <h3>Sales by Cashier</h3>
                <span>{{ $byCashier->count() }} cashiers</span>
            </div>

            <div class="rp-table-wrap">
                <table class="rp-table" id="cashiersTable">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($byCashier as $row)
                            <tr>
                                <td class="rp-name">{{ $row->name }}</td>
                                <td>{{ number_format($row->orders) }}</td>
                                <td class="rp-money">
                                    {{ $currency }} {{ $formatMoney($row->revenue) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="rp-empty">
                                    <div class="rp-empty-icon">♙</div>
                                    No cashier sales in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rp-summary-footer">
                <span>Total cashier revenue</span>
                <strong>{{ $currency }} {{ $formatMoney($cashierRevenue) }}</strong>
            </div>
        </div>

    </div>

    {{-- Payment method report --}}
    <div class="rp-section-heading">
        <div>
            <h3>Payment Analysis</h3>
            <p>Review how customers are paying for purchases.</p>
        </div>
    </div>

    <div class="rp-grid-two">

        <div class="rp-card">
            <div class="rp-card-header">
                <h3>Sales by Payment Method</h3>
                <span>{{ $byPayment->count() }} methods</span>
            </div>

            <div class="rp-card-body">
                @if($byPayment->isNotEmpty())
                    <div class="rp-method-list">
                        @foreach($byPayment as $row)
                            @php
                                $percentage = $paymentTotal > 0
                                    ? min(100, max(0, ((float) $row->total / $paymentTotal) * 100))
                                    : 0;
                            @endphp

                            <div>
                                <div class="rp-method-top">
                                    <span class="rp-method-name">
                                        {{ ucfirst($row->method) }}
                                    </span>

                                    <span class="rp-method-amount">
                                        {{ $currency }} {{ $formatMoney($row->total) }}
                                    </span>
                                </div>

                                <div class="rp-method-track">
                                    <span style="width: {{ $percentage }}%"></span>
                                </div>

                                <div class="rp-method-meta">
                                    {{ number_format($row->payments) }} payments
                                    · {{ number_format($percentage, 1) }}% of payment total
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rp-empty">
                        <div class="rp-empty-icon">◉</div>
                        No payments in this period.
                    </div>
                @endif
            </div>

            <div class="rp-summary-footer">
                <span>Total payments</span>
                <strong>{{ $currency }} {{ $formatMoney($paymentTotal) }}</strong>
            </div>
        </div>

        {{-- Payment table --}}
        <div class="rp-card">
            <div class="rp-card-header">
                <h3>Payment Summary</h3>
                <span>By payment type</span>
            </div>

            <div class="rp-table-wrap">
                <table class="rp-table" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Payments</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($byPayment as $row)
                            <tr>
                                <td class="rp-name">{{ ucfirst($row->method) }}</td>
                                <td>{{ number_format($row->payments) }}</td>
                                <td class="rp-money">
                                    {{ $currency }} {{ $formatMoney($row->total) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="rp-empty">
                                    No payment records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rp-summary-footer">
                <span>Payment records</span>
                <strong>{{ number_format($byPayment->sum('payments')) }}</strong>
            </div>
        </div>

    </div>

    {{-- Inventory alerts --}}
    <div class="rp-section-heading">
        <div>
            <h3>Inventory Alerts</h3>
            <p>Products that may need replenishment.</p>
        </div>

        @if($lowStock->isNotEmpty())
            <span class="rp-badge rp-badge-warning">
                {{ $lowStock->count() }} items need attention
            </span>
        @else
            <span class="rp-badge">Stock levels look good</span>
        @endif
    </div>

    <div class="rp-card">
        <div class="rp-card-header">
            <h3>Low Stock Products</h3>
            <span>Current inventory levels</span>
        </div>

        <div class="rp-table-wrap">
            <table class="rp-table" id="lowStockTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Available Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($lowStock as $p)
                        @php
                            $stock = (float) $p->stock;
                            $reorder = (float) $p->reorder_level;
                            $isOut = $stock <= 0;
                        @endphp

                        <tr>
                            <td class="rp-name">{{ $p->name }}</td>

                            <td>
                                <strong>{{ number_format($stock, 2) }}</strong>
                            </td>

                            <td>{{ number_format($reorder, 2) }}</td>

                            <td>
                                @if($isOut)
                                    <span class="rp-badge rp-badge-danger">
                                        Out of Stock
                                    </span>
                                @else
                                    <span class="rp-badge rp-badge-warning">
                                        Low Stock
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="rp-empty">
                                <div class="rp-empty-icon">✓</div>
                                No low-stock products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rp-summary-footer">
            <span>Products requiring attention</span>
            <strong>{{ number_format($lowStock->count()) }}</strong>
        </div>
    </div>

    <div class="rp-summary-footer" style="padding:14px 2px;border:0">
        <span>
            Report period: {{ $from->toDateString() }} to {{ $to->toDateString() }}
        </span>
        <span>Generated {{ now()->format('d M Y, H:i') }}</span>
    </div>

</div>

<script>
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);

    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tr'));

    const csv = rows.map(row => {
        return Array.from(row.querySelectorAll('th, td')).map(cell => {
            let value = cell.innerText
                .replace(/\s+/g, ' ')
                .trim();

            return '"' + value.replace(/"/g, '""') + '"';
        }).join(',');
    }).join('\n');

    downloadCSV(csv, filename);
}

function downloadCSV(content, filename) {
    const blob = new Blob(['\uFEFF' + content], {
        type: 'text/csv;charset=utf-8;'
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(url);
}

function exportAllReports() {
    const sections = [
        ['Sales Overview', [
            ['Metric', 'Value'],
            ['Paid Orders', '{{ $orders }}'],
            ['Revenue', '{{ $revenue }}'],
            ['Average Sale', '{{ $average }}'],
            ['Currency', '{{ $currency }}'],
            ['From', '{{ $from->toDateString() }}'],
            ['To', '{{ $to->toDateString() }}']
        ]],
        ['Top Products', documentTableData('productsTable')],
        ['Sales by Cashier', documentTableData('cashiersTable')],
        ['Payments', documentTableData('paymentsTable')],
        ['Low Stock', documentTableData('lowStockTable')]
    ];

    const csv = sections.map(section => {
        return section[0] + '\n' +
            section[1].map(row =>
                row.map(value =>
                    '"' + String(value ?? '').replace(/"/g, '""') + '"'
                ).join(',')
            ).join('\n');
    }).join('\n\n');

    downloadCSV(csv, 'business-report-{{ $from->toDateString() }}-to-{{ $to->toDateString() }}.csv');
}

function documentTableData(tableId) {
    const table = document.getElementById(tableId);

    if (!table) return [];

    return Array.from(table.querySelectorAll('tr')).map(row =>
        Array.from(row.querySelectorAll('th, td')).map(cell =>
            cell.innerText.replace(/\s+/g, ' ').trim()
        )
    );
}
</script>

@endsection
