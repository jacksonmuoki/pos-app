
@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('page-heading', 'Reports & Analytics')

@section('content')

@php
    $orders = (int) ($summary->orders ?? 0);
    $revenue = (float) ($summary->revenue ?? 0);
    $average = (float) ($summary->average ?? 0);
    $unitsSold = (int) ($unitsSold ?? 0);

    $lowStockCount = $lowStock->count();
    $paymentTotal = (float) $byPayment->sum('total');
    $paymentCount = (int) $byPayment->sum('payments');

    $formatMoney = fn ($amount) => number_format((float) $amount, 2);

    $topProduct = $insights['top_product'] ?? null;
    $topCashier = $insights['top_cashier'] ?? null;
    $topPayment = $insights['top_payment'] ?? null;
@endphp

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

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
    width: 100%;
    min-width: 0;
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

/* REPORT BUTTONS */

.rp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 39px;
    padding: 9px 14px;
    border: 1px solid var(--rp-border);
    border-radius: 9px;
    background: var(--rp-card);
    color: var(--rp-text);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: .2s ease;
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

/* SECTION HEADINGS */

.rp-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: 25px 0 13px;
}

.rp-section-heading h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 900;
}

.rp-section-heading p {
    margin: 4px 0 0;
    font-size: 11px;
    color: var(--rp-muted);
}

.rp-period {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 8px;
    background: var(--rp-soft);
    color: var(--rp-green);
    font-size: 11px;
    font-weight: 750;
}

/* KPI CARDS */

.rp-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
}

.rp-kpi {
    position: relative;
    min-width: 0;
    padding: 17px;
    border: 1px solid var(--rp-border);
    border-radius: 13px;
    background: var(--rp-card);
    transition: transform .2s, box-shadow .2s;
}

.rp-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 20px rgba(0,0,0,.045);
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
    display: flex;
    justify-content: center;
    align-items: center;
    width: 37px;
    height: 37px;
    border-radius: 10px;
    background: var(--rp-soft);
    color: var(--rp-green);
    font-size: 16px;
}

.rp-kpi-value {
    margin-top: 14px;
    font-size: clamp(19px, 2vw, 25px);
    font-weight: 850;
    letter-spacing: -.7px;
    overflow-wrap: anywhere;
}

.rp-kpi-note {
    margin-top: 5px;
    font-size: 11px;
    color: var(--rp-muted);
}

/* GRID AND CARDS */

.rp-grid-two {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.rp-card {
    min-width: 0;
    margin-bottom: 15px;
    overflow: hidden;
    border: 1px solid var(--rp-border);
    border-radius: 14px;
    background: var(--rp-card);
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
    margin: 0;
    font-size: 13px;
    font-weight: 800;
}

.rp-card-header span {
    color: var(--rp-muted);
    font-size: 11px;
}

.rp-card-body {
    padding: 17px 18px;
}

/* CHARTS */

.rp-chart-area {
    position: relative;
    width: 100%;
    height: 290px;
}

.rp-chart-area.rp-chart-small {
    height: 260px;
}

.rp-chart-area canvas {
    max-width: 100%;
}

/* TABLES */

.rp-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.rp-table {
    width: 100%;
    min-width: 520px;
    border-collapse: collapse;
}

.rp-table th {
    padding: 12px 15px;
    border-bottom: 1px solid var(--rp-border);
    background: var(--rp-bg);
    color: var(--rp-muted);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .5px;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.rp-table td {
    padding: 13px 15px;
    border-bottom: 1px solid var(--rp-border);
    font-size: 12px;
    vertical-align: middle;
}

.rp-table tbody tr:last-child td {
    border-bottom: 0;
}

.rp-table tbody tr:hover {
    background: var(--rp-bg);
}

.rp-money {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.rp-name {
    font-weight: 750;
}

.rp-muted {
    color: var(--rp-muted);
}

.rp-rank {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: var(--rp-soft);
    color: var(--rp-green);
    font-size: 11px;
    font-weight: 800;
}

/* STATUS BADGES */

.rp-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
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

.rp-badge-danger {
    background: #fef2f2;
    color: #b91c1c;
}

[data-theme="dark"] .rp-badge-warning,
.dark .rp-badge-warning {
    background: #422b18;
    color: #fdba74;
}

[data-theme="dark"] .rp-badge-danger,
.dark .rp-badge-danger {
    background: #451d1d;
    color: #fca5a5;
}

/* PROGRESS */

.rp-progress {
    height: 6px;
    min-width: 65px;
    margin-top: 7px;
    overflow: hidden;
    border-radius: 20px;
    background: var(--rp-bg);
}

.rp-progress span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--rp-green);
}

/* PAYMENT METHODS */

.rp-method-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.rp-method-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    margin-top: 8px;
    overflow: hidden;
    border-radius: 20px;
    background: var(--rp-bg);
}

.rp-method-track span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--rp-green);
}

.rp-method-meta {
    margin-top: 5px;
    color: var(--rp-muted);
    font-size: 10px;
}

/* BUSINESS INSIGHTS */

.rp-insights {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
}

.rp-insight {
    padding: 16px;
    border: 1px solid var(--rp-border);
    border-radius: 12px;
    background: var(--rp-card);
    min-width: 0;
}

.rp-insight-icon {
    margin-bottom: 12px;
    color: var(--rp-green);
    font-size: 18px;
}

.rp-insight-label {
    color: var(--rp-muted);
    font-size: 11px;
    font-weight: 700;
}

.rp-insight-value {
    margin-top: 6px;
    font-size: 14px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.rp-insight-note {
    margin-top: 5px;
    color: var(--rp-muted);
    font-size: 10px;
}

/* EMPTY STATES */

.rp-empty {
    padding: 28px 15px !important;
    color: var(--rp-muted);
    text-align: center;
}

.rp-empty i {
    display: block;
    margin-bottom: 9px;
    font-size: 22px;
}

/* SUMMARY FOOTER */

.rp-summary-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-top: 1px solid var(--rp-border);
    color: var(--rp-muted);
    font-size: 11px;
}

.rp-summary-footer strong {
    color: var(--rp-text);
}


/* REPORT BROWSER */

.rb-panel {
    background: var(--rp-card);
    border: 1px solid var(--rp-border);
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px rgba(20, 60, 35, .045);
}

.rb-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 22px;
}

.rb-heading h3 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: var(--rp-text);
}

.rb-heading h3 i {
    color: var(--rp-green);
    margin-right: 7px;
}

.rb-heading p {
    margin: 6px 0 0;
    color: var(--rp-muted);
    font-size: 13px;
}

.rb-heading-tag {
    padding: 7px 11px;
    border-radius: 20px;
    background: var(--rp-soft);
    color: var(--rp-green);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .6px;
}

.rb-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
}

.rb-field {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rb-field label {
    color: var(--rp-text);
    font-size: 12px;
    font-weight: 750;
}

/* DROPDOWN AND DATE INPUT BASE STYLE */

.rb-field select,
.rb-field input {
    display: block;
    width: 100%;
    min-width: 0;
    min-height: 46px;
    padding: 11px 14px;
    border: 1px solid var(--rp-border);
    border-radius: 10px;
    background-color: var(--rp-bg);
    color: var(--rp-text);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    outline: none;
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background-color .2s ease;
}

/* CUSTOM DROPDOWN ARROW */

.rb-field select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 42px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23718078' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 16px;
    cursor: pointer;
}

/* HOVER */

.rb-field select:hover,
.rb-field input:hover {
    border-color: var(--rp-green);
}

/* FOCUS */

.rb-field select:focus,
.rb-field input:focus {
    border-color: var(--rp-green);
    background-color: var(--rp-card);
    box-shadow: 0 0 0 3px rgba(21, 128, 61, .12);
}

/* DROPDOWN OPTIONS */

.rb-field select option {
    padding: 12px;
    background: var(--rp-card);
    color: var(--rp-text);
    font-weight: 600;
}

/* CUSTOM REPORT DROPDOWNS */

.rb-field {
    position: relative;
}

.rb-select-control {
    position: relative;
    width: 100%;
}

.rb-native-select {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

.rb-select-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
    min-height: 46px;
    padding: 11px 14px;
    border: 1px solid var(--rp-border);
    border-radius: 10px;
    background: var(--rp-bg);
    color: var(--rp-text);
    font-family: inherit;
    font-size: 13px;
    font-weight: 650;
    text-align: left;
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s, background .2s;
}

.rb-select-trigger:hover,
.rb-select-control.is-open .rb-select-trigger {
    border-color: var(--rp-green);
    background: var(--rp-card);
}

.rb-select-trigger:focus-visible {
    outline: none;
    border-color: var(--rp-green);
    box-shadow: 0 0 0 3px rgba(21, 128, 61, .13);
}

.rb-select-trigger i {
    color: var(--rp-muted);
    font-size: 12px;
    transition: transform .2s ease;
}

.rb-select-control.is-open .rb-select-trigger i {
    transform: rotate(180deg);
}

.rb-select-menu {
    position: absolute;
    top: calc(100% + 7px);
    left: 0;
    right: 0;
    z-index: 1000;
    display: none;
    max-height: 260px;
    padding: 6px;
    overflow-y: auto;
    border: 1px solid var(--rp-border);
    border-radius: 12px;
    background: var(--rp-card);
    box-shadow: 0 12px 30px rgba(0, 0, 0, .13);
    animation: rbDropdownOpen .16s ease;
}

.rb-select-control.is-open .rb-select-menu {
    display: block;
}

@keyframes rbDropdownOpen {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.rb-select-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 38px;
    padding: 9px 11px;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--rp-text);
    font-family: inherit;
    font-size: 13px;
    font-weight: 550;
    text-align: left;
    cursor: pointer;
    transition: background .15s, color .15s;
}

.rb-select-option:hover,
.rb-select-option:focus-visible {
    outline: none;
    background: var(--rp-soft);
    color: var(--rp-green);
}

.rb-select-option.is-selected {
    background: var(--rp-soft);
    color: var(--rp-green);
    font-weight: 750;
}

.rb-select-option .fa-check {
    color: var(--rp-green);
    font-size: 11px;
}

.rb-select-menu::-webkit-scrollbar {
    width: 6px;
}

.rb-select-menu::-webkit-scrollbar-thumb {
    border-radius: 10px;
    background: var(--rp-border);
}

[data-theme="dark"] .rb-select-menu,
.dark .rb-select-menu {
    box-shadow: 0 12px 30px rgba(0, 0, 0, .35);
}


/* DISABLED INPUTS */

.rb-field select:disabled,
.rb-field input:disabled {
    opacity: .55;
    cursor: not-allowed;
}

/* CUSTOM DATE FIELDS */

.rb-custom-dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
    margin-top: 17px;
}

.rb-custom-dates[hidden] {
    display: none;
}

/* REPORT BROWSER FOOTER */

.rb-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 22px;
    padding-top: 17px;
    border-top: 1px solid var(--rp-border);
}

.rb-footer p {
    margin: 0;
    color: var(--rp-muted);
    font-size: 12px;
}

.rb-footer p i {
    color: var(--rp-green);
    margin-right: 5px;
}

/* DARK MODE */

[data-theme="dark"] .rb-field select,
[data-theme="dark"] .rb-field input,
.dark .rb-field select,
.dark .rb-field input {
    color-scheme: dark;
}

[data-theme="dark"] .rb-field select:focus,
[data-theme="dark"] .rb-field input:focus,
.dark .rb-field select:focus,
.dark .rb-field input:focus {
    background-color: var(--rp-card);
}

/* RESPONSIVE */

@media(max-width: 900px) {
    .rb-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 560px) {
    .rb-panel {
        padding: 16px;
    }

    .rb-grid,
    .rb-custom-dates {
        grid-template-columns: 1fr;
    }

    .rb-footer {
        align-items: stretch;
        flex-direction: column;
    }

    .rb-footer .rp-btn {
        width: 100%;
    }
}


/* RESPONSIVE */

@media(max-width: 1000px) {
    .rp-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rp-insights {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 900px) {
    .rb-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 700px) {
    .rp-grid-two {
        grid-template-columns: 1fr;
    }

    .rp-chart-area {
        height: 250px;
    }
}

@media(max-width: 560px) {
    .rb-panel {
        padding: 16px;
    }

    .rb-grid,
    .rb-custom-dates {
        grid-template-columns: 1fr;
    }

    .rb-footer {
        align-items: stretch;
        flex-direction: column;
    }

    .rb-footer .rp-btn {
        width: 100%;
    }
}

@media(max-width: 480px) {
    .rp-kpis {
        gap: 9px;
    }

    .rp-kpi {
        padding: 13px;
    }

    .rp-kpi-value {
        font-size: 18px;
    }

    .rp-insights {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="reports-page">

    {{-- REPORT BROWSER --}}

    <div class="rb-panel">

        <div class="rb-heading">
            <div>
                <h3>
                    Report Browser
                </h3>

            </div>
        </div>

        <form method="GET"
              action="{{ route('reports.show', ['section' => 'business-overview']) }}"
              id="reportBrowserForm"
              target="_blank">

            <input type="hidden"
                   name="date_preset"
                   id="rbDatePreset"
                   value="today">

            <div class="rb-grid">

                <div class="rb-field">
                    <label for="rbMajor">Major Category</label>

                    <select id="rbMajor" required>
                        <option value="sales">Sales</option>
                        <option value="inventory">Inventory</option>
                        <option value="finance">Finance</option>
                        <option value="customers">Customers</option>
                        <option value="purchases">Purchases</option>
                        <option value="transactions">Transactions</option>
                        <option value="business">Business Overview</option>
                    </select>
                </div>

                <div class="rb-field">
                    <label for="rbMinor">Minor Category</label>

                    <select id="rbMinor" name="report" required>
                        <option value="sales-breakdown">Sales by Product</option>
                        <option value="sales-performance">Sales by Cashier</option>
                        <option value="business-overview">Daily Sales</option>
                    </select>
                </div>

                <div class="rb-field">
                    <label for="rbDate">Report Date</label>

                    <select id="rbDate" required>
                        <option value="today">Today</option>
                        <option value="tomorrow">Tomorrow</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <div class="rb-field">
                    <label for="rbType">Report Type</label>

                    <select id="rbType" name="report_type" required>
                        <option value="summary">Summary</option>
                        <option value="detailed">Detailed</option>
                    </select>
                </div>

            </div>

            <div class="rb-custom-dates" id="rbCustomDates" hidden>

                <div class="rb-field">
                    <label for="rbFrom">From Date</label>
                    <input type="date" id="rbFrom" name="from">
                </div>

                <div class="rb-field">
                    <label for="rbTo">To Date</label>
                    <input type="date" id="rbTo" name="to">
                </div>

            </div>

            <div class="rb-footer">

                <p>

                </p>

                <button type="submit" class="rp-btn rp-btn-primary">
                    <i class="fa-solid fa-chart-column"></i>
                    Generate Report
                </button>

            </div>

        </form>

    </div>


    {{-- SALES OVERVIEW --}}

    <div class="rp-section-heading">
        <div>
            <h3>Business Overview</h3>
        </div>

        <span class="rp-period">
            <i class="fa-regular fa-calendar"></i>
            {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
        </span>
    </div>

    <div class="rp-kpis">

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Paid Orders</span>

            </div>
            <div class="rp-kpi-value">{{ number_format($orders) }}</div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Total Revenue</span>
            </div>
            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($revenue) }}
            </div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Average Sale</span>
            </div>
            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($average) }}
            </div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Units Sold</span>
            </div>
            <div class="rp-kpi-value">{{ number_format($unitsSold) }}</div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Successful Payments</span>
            </div>
            <div class="rp-kpi-value">{{ number_format($paymentCount) }}</div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Low Stock Items</span>
            </div>
            <div class="rp-kpi-value">{{ number_format($lowStockCount) }}</div>
        </div>

    </div>

    {{-- SALES CHARTS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Sales Performance</h3>
        </div>
    </div>

    <div class="rp-grid-two">

        <div class="rp-card">
            <div class="rp-card-header">
                <h3>
                    <i class="fa-solid fa-chart-line"></i>
                    Revenue Trend
                </h3>
                <span>Daily performance</span>
            </div>

            <div class="rp-card-body">
                <div class="rp-chart-area">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="rp-card">
            <div class="rp-card-header">
                <h3>
                    <i class="fa-solid fa-chart-pie"></i>
                    Payment Distribution
                </h3>
                <span>Successful payments</span>
            </div>

            <div class="rp-card-body">
                <div class="rp-chart-area rp-chart-small">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>

        <div class="rp-card">
            <div class="rp-card-header">
                <h3>
                    <i class="fa-solid fa-ranking-star"></i>
                    Top-Selling Products
                </h3>
                <span>Top 8 by quantity</span>
            </div>

            <div class="rp-card-body">
                <div class="rp-chart-area">
                    <canvas id="productChart"></canvas>
                </div>
            </div>
        </div>

        <div class="rp-card">
            <div class="rp-card-header">
                <h3>
                    <i class="fa-solid fa-users"></i>
                    Cashier Performance
                </h3>
                <span>Revenue by cashier</span>
            </div>

            <div class="rp-card-body">
                <div class="rp-chart-area">
                    <canvas id="cashierChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- BUSINESS INSIGHTS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Business Insights</h3>
        </div>
    </div>

    <div class="rp-insights">

        <div class="rp-insight">

            <div class="rp-insight-label">Top Revenue Product</div>

            <div class="rp-insight-value">
                {{ $topProduct->name ?? 'No product sales' }}
            </div>

            <div class="rp-insight-note">
                @if($topProduct)
                    {{ number_format((float) $topProduct->qty) }} units sold
                    · {{ $currency }} {{ $formatMoney($topProduct->revenue) }}
                @else
                    No product sales in this period.
                @endif
            </div>
        </div>

        <div class="rp-insight">

            <div class="rp-insight-label">Highest Revenue Cashier</div>

            <div class="rp-insight-value">
                {{ $topCashier->name ?? 'No cashier sales' }}
            </div>

            <div class="rp-insight-note">
                @if($topCashier)
                    {{ number_format($topCashier->orders) }} orders
                    · {{ $currency }} {{ $formatMoney($topCashier->revenue) }}
                @else
                    No cashier sales in this period.
                @endif
            </div>
        </div>

        <div class="rp-insight">

            <div class="rp-insight-label">Leading Payment Method</div>

            <div class="rp-insight-value">
                {{ $topPayment ? ucfirst(str_replace('_', ' ', $topPayment->method)) : 'No payment data' }}
            </div>

            <div class="rp-insight-note">
                @if($topPayment)
                    {{ number_format($topPayment->payments) }} payments
                    · {{ $currency }} {{ $formatMoney($topPayment->total) }}
                @else
                    No successful payments in this period.
                @endif
            </div>
        </div>

    </div>

    {{-- PRODUCT AND CASHIER TABLES --}}

    <div class="rp-section-heading">
        <div>
            <h3>Sales Breakdown</h3>
        </div>
    </div>

    <div class="rp-grid-two">

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
                                    <span class="rp-rank">
                                        {{ $loop->iteration }}
                                    </span>
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
                                    <i class="fa-solid fa-box-open"></i>
                                    No product sales in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rp-summary-footer">
                <span></span>
                <strong>
                    {{ $currency }} {{ $formatMoney($byProduct->sum('revenue')) }}
                </strong>
            </div>
        </div>

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
                                    <i class="fa-solid fa-users"></i>
                                    No cashier sales in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rp-summary-footer">
                <span>Total cashier revenue</span>
                <strong>
                    {{ $currency }} {{ $formatMoney($byCashier->sum('revenue')) }}
                </strong>
            </div>
        </div>

    </div>

    {{-- PAYMENT ANALYSIS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Payment Analysis</h3>
        </div>
    </div>

    <div class="rp-grid-two">

        <div class="rp-card">
            <div class="rp-card-header">
                <h3>Payment Method Breakdown</h3>
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
                                        {{ ucfirst(str_replace('_', ' ', $row->method)) }}
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
                                    · {{ number_format($percentage, 1) }}%
                                </div>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="rp-empty">
                        <i class="fa-solid fa-credit-card"></i>
                        No successful payments in this period.
                    </div>

                @endif

            </div>

            <div class="rp-summary-footer">
                <span>Total successful payments</span>
                <strong>
                    {{ $currency }} {{ $formatMoney($paymentTotal) }}
                </strong>
            </div>
        </div>

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
                                <td class="rp-name">
                                    {{ ucfirst(str_replace('_', ' ', $row->method)) }}
                                </td>

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
                <span>Successful payment records</span>
                <strong>{{ number_format($paymentCount) }}</strong>
            </div>
        </div>

    </div>

    {{-- INVENTORY ALERTS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Inventory Alerts</h3>
        </div>

        @if($lowStock->isNotEmpty())
            <span class="rp-badge rp-badge-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                {{ $lowStockCount }} items need attention
            </span>
        @else
            <span class="rp-badge">
                <i class="fa-solid fa-circle-check"></i>
                Stock levels look good
            </span>
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

                    @forelse($lowStock as $product)

                        @php
                            $stock = (float) $product->stock;
                            $reorder = (float) $product->reorder_level;
                            $isOut = $stock <= 0;
                        @endphp

                        <tr>
                            <td class="rp-name">{{ $product->name }}</td>

                            <td>
                                <strong>{{ number_format($stock, 2) }}</strong>
                            </td>

                            <td>{{ number_format($reorder, 2) }}</td>

                            <td>
                                @if($isOut)
                                    <span class="rp-badge rp-badge-danger">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        Out of Stock
                                    </span>
                                @else
                                    <span class="rp-badge rp-badge-warning">
                                        <i class="fa-solid fa-exclamation-circle"></i>
                                        Low Stock
                                    </span>
                                @endif
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="rp-empty">
                                <i class="fa-solid fa-circle-check"></i>
                                No low-stock products found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

    </div>

    {{-- PROFITABILITY ANALYSIS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Profitability Analysis</h3>
        </div>

    </div>

    <div class="rp-kpis">

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Estimated Gross Profit</span>

            </div>

            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($estimatedGrossProfit) }}
            </div>

        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Estimated Profit Margin</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($estimatedProfitMargin, 1) }}%
            </div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Discounts Given</span>
            </div>

            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($summary->discounts) }}
            </div>

        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Tax Collected</span>
            </div>

            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($summary->tax) }}
            </div>
        </div>

    </div>

    <div class="rp-card">

        <div class="rp-card-header">
            <h3>Product Profitability</h3>
        </div>

        <div class="rp-table-wrap">

            <table class="rp-table" id="profitabilityTable">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty Sold</th>
                        <th>Revenue</th>
                        <th>Estimated Cost</th>
                        <th>Estimated Profit</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($productProfitability as $row)

                        <tr>
                            <td class="rp-name">{{ $row->name }}</td>

                            <td>{{ number_format($row->qty) }}</td>

                            <td>
                                {{ $currency }} {{ $formatMoney($row->revenue) }}
                            </td>

                            <td>
                                {{ $currency }} {{ $formatMoney($row->estimated_cost) }}
                            </td>

                            <td class="rp-money">
                                {{ $currency }} {{ $formatMoney($row->estimated_profit) }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="rp-empty">
                                No product profitability data available.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="rp-summary-footer">
            <span></span>
            <strong>
                {{ $currency }} {{ $formatMoney($estimatedGrossProfit) }}
            </strong>
        </div>

    </div>

    {{-- CUSTOMER ANALYTICS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Customer Analytics</h3>
        </div>
    </div>

    <div class="rp-kpis">

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Registered Customers</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($totalCustomers) }}
            </div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">New Customers</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($newCustomers) }}
            </div>

        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Customers Who Purchased</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($customersWithOrders) }}
            </div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Repeat Customers</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($returningCustomers) }}
            </div>
        </div>

    </div>

    <div class="rp-card">

        <div class="rp-card-header">
            <h3>Top Customers</h3>
            <span>Ranked by spending</span>
        </div>

        <div class="rp-table-wrap">

            <table class="rp-table" id="customersTable">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Total Spending</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($topCustomers as $customer)

                        <tr>
                            <td>
                                <span class="rp-rank">
                                    {{ $loop->iteration }}
                                </span>
                            </td>

                            <td class="rp-name">
                                {{ $customer->name }}
                            </td>

                            <td>{{ $customer->phone ?: '—' }}</td>

                            <td>{{ number_format($customer->orders) }}</td>

                            <td class="rp-money">
                                {{ $currency }} {{ $formatMoney($customer->spending) }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="rp-empty">
                                No customer purchases found in this period.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="rp-summary-footer">
            <span>Average spending per purchasing customer</span>
            <strong>
                {{ $currency }} {{ $formatMoney($customerAverageSpend) }}
            </strong>
        </div>

    </div>

    {{-- PURCHASE AND SUPPLIER ANALYTICS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Purchases & Suppliers</h3>
        </div>
    </div>

    <div class="rp-kpis">

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Total Purchases</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($purchaseSummary->purchases ?? 0) }}
            </div>
        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Purchase Expenditure</span>
            </div>

            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($purchaseSummary->total ?? 0) }}
            </div>
        </div>

    </div>

    <div class="rp-card">

        <div class="rp-card-header">
            <h3>Purchases by Supplier</h3>
            <span>{{ $purchasesBySupplier->count() }} suppliers</span>
        </div>

        <div class="rp-table-wrap">

            <table class="rp-table" id="suppliersTable">

                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Purchases</th>
                        <th>Total Expenditure</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($purchasesBySupplier as $supplier)

                        <tr>
                            <td class="rp-name">{{ $supplier->supplier }}</td>

                            <td>{{ number_format($supplier->purchases) }}</td>

                            <td class="rp-money">
                                {{ $currency }} {{ $formatMoney($supplier->total) }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="3" class="rp-empty">
                                No purchases found in this period.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- INVENTORY VALUATION --}}

    <div class="rp-section-heading">
        <div>
            <h3>Inventory Valuation & Stock Movement</h3>
        </div>
    </div>

    <div class="rp-kpis">

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Total Products</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($inventorySummary->products ?? 0) }}
            </div>


        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Units in Stock</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($inventorySummary->units_in_stock ?? 0) }}
            </div>


        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Inventory Cost Value</span>
            </div>

            <div class="rp-kpi-value">
                {{ $currency }} {{ $formatMoney($inventorySummary->stock_value ?? 0) }}
            </div>


        </div>

        <div class="rp-kpi">
            <div class="rp-kpi-top">
                <span class="rp-kpi-label">Out of Stock</span>
            </div>

            <div class="rp-kpi-value">
                {{ number_format($inventorySummary->out_of_stock ?? 0) }}
            </div>


        </div>

    </div>

    <div class="rp-grid-two">

        <div class="rp-card">

            <div class="rp-card-header">
                <h3>Out-of-Stock Products</h3>
                <span>{{ $outOfStockProducts->count() }} shown</span>
            </div>

            <div class="rp-table-wrap">

                <table class="rp-table" id="outOfStockTable">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Price</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($outOfStockProducts as $product)

                            <tr>
                                <td class="rp-name">{{ $product->name }}</td>

                                <td>{{ number_format($product->stock, 2) }}</td>

                                <td>
                                    {{ $currency }} {{ $formatMoney($product->price) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="3" class="rp-empty">
                                    No out-of-stock products found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        <div class="rp-card">

            <div class="rp-card-header">
                <h3>Products Without Sales</h3>
                <span>Selected reporting period</span>
            </div>

            <div class="rp-table-wrap">

                <table class="rp-table" id="noSalesTable">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Price</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($noSalesProducts as $product)

                            <tr>
                                <td class="rp-name">{{ $product->name }}</td>

                                <td>{{ number_format($product->stock, 2) }}</td>

                                <td>
                                    {{ $currency }} {{ $formatMoney($product->price) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="3" class="rp-empty">
                                    All products have recorded sales, or no products exist.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- STOCK ADJUSTMENT SUMMARY --}}

    <div class="rp-card">

        <div class="rp-card-header">
            <h3>Stock Adjustment Summary</h3>
            <span>Selected reporting period</span>
        </div>

        <div class="rp-kpis" style="padding:16px">

            <div class="rp-kpi">
                <div class="rp-kpi-label">Adjustments</div>
                <div class="rp-kpi-value">
                    {{ number_format($stockAdjustmentSummary->adjustments ?? 0) }}
                </div>
            </div>

            <div class="rp-kpi">
                <div class="rp-kpi-label">Stock Added</div>
                <div class="rp-kpi-value">
                    {{ number_format($stockAdjustmentSummary->added ?? 0) }}
                </div>
            </div>

            <div class="rp-kpi">
                <div class="rp-kpi-label">Stock Removed</div>
                <div class="rp-kpi-value">
                    {{ number_format($stockAdjustmentSummary->removed ?? 0) }}
                </div>
            </div>

            <div class="rp-kpi">
                <div class="rp-kpi-label">Net Stock Change</div>
                <div class="rp-kpi-value">
                    {{ number_format($stockAdjustmentSummary->net_change ?? 0) }}
                </div>
            </div>

        </div>

    </div>

    {{-- TRANSACTION STATUS ANALYSIS --}}

    <div class="rp-section-heading">
        <div>
            <h3>Transaction Status Analysis</h3>
        </div>
    </div>

    <div class="rp-grid-two">

        <div class="rp-card">

            <div class="rp-card-header">
                <h3>Orders by Status</h3>
                <span>{{ $ordersByStatus->sum('orders') }} orders</span>
            </div>

            <div class="rp-table-wrap">

                <table class="rp-table" id="orderStatusTable">

                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($ordersByStatus as $row)

                            <tr>
                                <td class="rp-name">
                                    {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                                </td>

                                <td>{{ number_format($row->orders) }}</td>

                                <td>
                                    {{ $currency }} {{ $formatMoney($row->total) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="3" class="rp-empty">
                                    No orders found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        <div class="rp-card">

            <div class="rp-card-header">
                <h3>Payments by Status</h3>
                <span>{{ $paymentStatuses->sum('payments') }} records</span>
            </div>

            <div class="rp-table-wrap">

                <table class="rp-table" id="paymentStatusTable">

                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Payments</th>
                            <th>Amount</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($paymentStatuses as $row)

                            <tr>
                                <td class="rp-name">
                                    {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                                </td>

                                <td>{{ number_format($row->payments) }}</td>

                                <td>
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

        </div>

    </div>

    {{-- PERIOD COMPARISON --}}

    <div class="rp-section-heading">
        <div>
            <h3>Period Comparison</h3>
        </div>
    </div>

    <div class="rp-card">

        <div class="rp-card-header">
            <h3>Sales Performance Comparison</h3>

            <span>
                {{ $previousFrom->format('d M Y') }} –
                {{ $previousTo->format('d M Y') }}
            </span>
        </div>

        <div class="rp-table-wrap">

            <table class="rp-table" id="comparisonTable">

                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Previous Period</th>
                        <th>Selected Period</th>
                        <th>Change</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td class="rp-name">Revenue</td>

                        <td>
                            {{ $currency }} {{ $formatMoney($previousSummary->revenue) }}
                        </td>

                        <td>
                            {{ $currency }} {{ $formatMoney($summary->revenue) }}
                        </td>

                        <td>
                            {{ $revenueChange === null ? 'N/A' : number_format($revenueChange, 1) . '%' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="rp-name">Paid Orders</td>

                        <td>
                            {{ number_format($previousSummary->orders) }}
                        </td>

                        <td>
                            {{ number_format($summary->orders) }}
                        </td>

                        <td>
                            {{ $ordersChange === null ? 'N/A' : number_format($ordersChange, 1) . '%' }}
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

    {{-- FOOTER --}}

    <div class="rp-summary-footer"
         style="padding:14px 2px;border:0">

        <span>
            Report period:
            {{ $from->toDateString() }} to {{ $to->toDateString() }}
        </span>

        <span>
            Generated {{ now()->format('d M Y, H:i') }}
        </span>

    </div>

</div>

{{-- REPORT BROWSER JAVASCRIPT --}}

<script>
document.addEventListener('DOMContentLoaded', function () {

    const major = document.getElementById('rbMajor');
    const minor = document.getElementById('rbMinor');
    const date = document.getElementById('rbDate');
    const preset = document.getElementById('rbDatePreset');
    const customDates = document.getElementById('rbCustomDates');
    const from = document.getElementById('rbFrom');
    const to = document.getElementById('rbTo');
    const form = document.getElementById('reportBrowserForm');

    if (!major || !minor || !date || !form) return;

    const classifications = {

        sales: [
            ['sales-breakdown', 'Sales by Product'],
            ['sales-performance', 'Sales by Cashier'],
            ['business-overview', 'Daily Sales']
        ],

        inventory: [
            ['inventory-alerts', 'Stock Levels and Low Stock'],
            ['inventory-stock-movement', 'Stock Movement']
        ],

        finance: [
            ['profitability-analysis', 'Profitability Analysis'],
            ['payment-analysis', 'Payment Analysis']
        ],

        customers: [
            ['customer-analytics', 'Customer Analytics']
        ],

        purchases: [
            ['purchases-suppliers', 'Purchases by Supplier']
        ],

        transactions: [
            ['transaction-status', 'Transaction Status']
        ],

        business: [
            ['business-overview', 'Business Overview'],
            ['business-insights', 'Business Insights'],
            ['period-comparison', 'Period Comparison']
        ]

    };

    // ------------------------------------------------
    // CUSTOM DROPDOWN SYSTEM
    // ------------------------------------------------

    const customDropdowns = new Map();

    function refreshCustomSelect(select) {

        const control = customDropdowns.get(select);

        if (!control) return;

        const menu = control.menu;
        const trigger = control.trigger;

        menu.innerHTML = '';

        Array.from(select.options).forEach(function (option) {

            const item = document.createElement('button');

            item.type = 'button';
            item.className = 'rb-select-option';
            item.setAttribute('role', 'option');

            item.textContent = option.textContent;

            const selected = option.value === select.value;

            item.setAttribute('aria-selected', selected ? 'true' : 'false');

            if (selected) {
                item.classList.add('is-selected');

                const check = document.createElement('i');
                check.className = 'fa-solid fa-check';

                item.appendChild(check);
            }

            item.addEventListener('click', function () {

                select.value = option.value;

                select.dispatchEvent(
                    new Event('change', { bubbles: true })
                );

                refreshCustomSelect(select);
                closeAllDropdowns();

                trigger.focus();

            });

            menu.appendChild(item);

        });

        const selectedOption = select.options[select.selectedIndex];

        control.label.textContent = selectedOption
            ? selectedOption.textContent
            : 'Select an option';

    }

    function closeAllDropdowns(except = null) {

        customDropdowns.forEach(function (control) {

            if (control.wrapper === except) return;

            control.wrapper.classList.remove('is-open');

            control.trigger.setAttribute('aria-expanded', 'false');

        });

    }

    function buildCustomSelect(select) {

        const wrapper = document.createElement('div');

        wrapper.className = 'rb-select-control';

        const trigger = document.createElement('button');

        trigger.type = 'button';
        trigger.className = 'rb-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        const label = document.createElement('span');

        const arrow = document.createElement('i');

        arrow.className = 'fa-solid fa-chevron-down';

        trigger.appendChild(label);
        trigger.appendChild(arrow);

        const menu = document.createElement('div');

        menu.className = 'rb-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;

        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);

        select.insertAdjacentElement('afterend', wrapper);

        select.classList.add('rb-native-select');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const control = {
            wrapper: wrapper,
            trigger: trigger,
            label: label,
            menu: menu
        };

        customDropdowns.set(select, control);

        trigger.addEventListener('click', function () {

            const isOpen = wrapper.classList.contains('is-open');

            closeAllDropdowns();

            if (!isOpen) {

                wrapper.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');

            }

        });

        trigger.addEventListener('keydown', function (event) {

            if (
                event.key === 'ArrowDown' ||
                event.key === 'Enter' ||
                event.key === ' '
            ) {

                event.preventDefault();

                closeAllDropdowns();

                wrapper.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');

                const firstOption = menu.querySelector('.rb-select-option');

                if (firstOption) firstOption.focus();

            }

        });

        menu.addEventListener('keydown', function (event) {

            const items = Array.from(
                menu.querySelectorAll('.rb-select-option')
            );

            const currentIndex = items.indexOf(document.activeElement);

            if (event.key === 'ArrowDown') {

                event.preventDefault();

                items[Math.min(currentIndex + 1, items.length - 1)]?.focus();

            } else if (event.key === 'ArrowUp') {

                event.preventDefault();

                if (currentIndex <= 0) {
                    trigger.focus();
                } else {
                    items[currentIndex - 1]?.focus();
                }

            } else if (event.key === 'Escape') {

                event.preventDefault();

                closeAllDropdowns();
                trigger.focus();

            }

        });

        select.addEventListener('change', function () {
            refreshCustomSelect(select);
        });

        refreshCustomSelect(select);

    }

    // Build custom controls for all four dropdowns.

    form.querySelectorAll('select').forEach(function (select) {
        buildCustomSelect(select);
    });

    // ------------------------------------------------
    // CATEGORY OPTIONS
    // ------------------------------------------------

    function updateMinorOptions() {

        const options = classifications[major.value] || [];

        minor.innerHTML = '';

        options.forEach(function (item) {

            const option = document.createElement('option');

            option.value = item[0];
            option.textContent = item[1];

            minor.appendChild(option);

        });

        refreshCustomSelect(minor);

        updateFormAction();

    }

    // ------------------------------------------------
    // REPORT URL
    // ------------------------------------------------

    const baseAction = form.action;

    function updateFormAction() {

        const url = new URL(baseAction, window.location.origin);

        const parts = url.pathname.split('/');

        parts[parts.length - 1] = minor.value;

        url.pathname = parts.join('/');

        form.action = url.toString();

    }

    // ------------------------------------------------
    // DATE FIELDS
    // ------------------------------------------------

    function updateDateFields() {

        const isCustom = date.value === 'custom';

        customDates.hidden = !isCustom;

        from.required = isCustom;
        to.required = isCustom;

        from.disabled = !isCustom;
        to.disabled = !isCustom;

        preset.value = date.value;

    }

    // ------------------------------------------------
    // EVENTS
    // ------------------------------------------------

    major.addEventListener('change', updateMinorOptions);

    minor.addEventListener('change', updateFormAction);

    date.addEventListener('change', updateDateFields);

    document.addEventListener('click', function (event) {

        if (!event.target.closest('.rb-select-control')) {
            closeAllDropdowns();
        }

    });

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeAllDropdowns();
        }

    });

    form.addEventListener('submit', function (event) {

        updateFormAction();

        if (date.value === 'custom') {

            if (!from.value || !to.value) {

                event.preventDefault();

                alert('Please select both the From Date and To Date.');

                return;

            }

            if (from.value > to.value) {

                event.preventDefault();

                alert('The From Date cannot be after the To Date.');

                return;

            }

        }

    });

    // Initial setup

    updateMinorOptions();

    updateDateFields();

});
</script>


{{-- CHART.JS --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------
    // CHART DATA FROM LARAVEL
    // ---------------------------------------

    const salesLabels = @json($salesTrend['labels']);
    const salesRevenue = @json($salesTrend['revenue']);
    const salesOrders = @json($salesTrend['orders']);

    const productLabels = @json($productChart['labels']);
    const productValues = @json($productChart['values']);

    const cashierLabels = @json($cashierChart['labels']);
    const cashierValues = @json($cashierChart['values']);

    const paymentLabels = @json($paymentChart['labels']);
    const paymentValues = @json($paymentChart['values']);

    const currency = @json($currency);

    const darkMode =
        document.documentElement.getAttribute('data-theme') === 'dark' ||
        document.body.classList.contains('dark') ||
        document.querySelector('.dark .reports-page') !== null;

    const textColor = darkMode ? '#cbd5ce' : '#64736a';
    const gridColor = darkMode ? '#2a3a30' : '#e8eee9';

    // Shared chart defaults

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = textColor;

    const moneyTooltip = value => {

        return currency + ' ' + Number(value).toLocaleString(
            'en-KE',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    };

    // ---------------------------------------
    // 1. DAILY SALES TREND
    // ---------------------------------------

    const salesCanvas = document.getElementById('salesTrendChart');

    if (salesCanvas) {

        new Chart(salesCanvas, {

            type: 'line',

            data: {

                labels: salesLabels,

                datasets: [

                    {
                        label: 'Revenue',
                        data: salesRevenue,
                        borderColor: '#16834a',
                        backgroundColor: 'rgba(22, 131, 74, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        yAxisID: 'y'
                    },

                    {
                        label: 'Paid Orders',
                        data: salesOrders,
                        borderColor: '#d97706',
                        backgroundColor: '#d97706',
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2,
                        borderDash: [5, 4],
                        yAxisID: 'y1'
                    }

                ]

            },

            options: {

                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false
                },

                plugins: {

                    legend: {

                        position: 'bottom',

                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 18
                        }

                    },

                    tooltip: {

                        callbacks: {

                            label: context => {

                                if (context.dataset.label === 'Revenue') {

                                    return 'Revenue: ' +
                                        moneyTooltip(context.parsed.y);

                                }

                                return 'Paid Orders: ' + context.parsed.y;

                            }

                        }

                    }

                },

                scales: {

                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0 }
                    },

                    y: {

                        beginAtZero: true,

                        grid: {
                            color: gridColor
                        },

                        ticks: {

                            callback: value =>
                                currency + ' ' +
                                Number(value).toLocaleString()

                        }

                    },

                    y1: {

                        beginAtZero: true,
                        position: 'right',

                        grid: {
                            drawOnChartArea: false
                        },

                        ticks: {
                            precision: 0
                        }

                    }

                }

            }

        });

    }

    // ---------------------------------------
    // 2. PAYMENT DISTRIBUTION
    // ---------------------------------------

    const paymentCanvas = document.getElementById('paymentChart');

    if (paymentCanvas) {

        new Chart(paymentCanvas, {

            type: 'doughnut',

            data: {

                labels: paymentLabels,

                datasets: [{

                    data: paymentValues,

                    backgroundColor: [
                        '#16834a',
                        '#d97706',
                        '#2563eb',
                        '#9333ea',
                        '#0891b2',
                        '#db2777'
                    ],

                    borderWidth: 0,
                    hoverOffset: 7

                }]

            },

            options: {

                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',

                plugins: {

                    legend: {

                        position: 'bottom',

                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 15
                        }

                    },

                    tooltip: {

                        callbacks: {

                            label: context => {

                                return context.label + ': ' +
                                    moneyTooltip(context.parsed);

                            }

                        }

                    }

                }

            }

        });

    }

    // ---------------------------------------
    // 3. TOP SELLING PRODUCTS
    // ---------------------------------------

    const productCanvas = document.getElementById('productChart');

    if (productCanvas) {

        new Chart(productCanvas, {

            type: 'bar',

            data: {

                labels: productLabels,

                datasets: [{

                    label: 'Units Sold',
                    data: productValues,
                    backgroundColor: '#16834a',
                    borderRadius: 6,
                    maxBarThickness: 24

                }]

            },

            options: {

                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {

                        callbacks: {

                            label: context =>
                                'Units Sold: ' + context.parsed.x

                        }

                    }

                },

                scales: {

                    x: {

                        beginAtZero: true,

                        grid: {
                            color: gridColor
                        },

                        ticks: {
                            precision: 0
                        }

                    },

                    y: {
                        grid: { display: false }
                    }

                }

            }

        });

    }

    // ---------------------------------------
    // 4. CASHIER PERFORMANCE
    // ---------------------------------------

    const cashierCanvas = document.getElementById('cashierChart');

    if (cashierCanvas) {

        new Chart(cashierCanvas, {

            type: 'bar',

            data: {

                labels: cashierLabels,

                datasets: [{

                    label: 'Revenue',
                    data: cashierValues,
                    backgroundColor: '#16834a',
                    borderRadius: 6,
                    maxBarThickness: 28

                }]

            },

            options: {

                responsive: true,
                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {

                        callbacks: {

                            label: context =>
                                moneyTooltip(context.parsed.y)

                        }

                    }

                },

                scales: {

                    x: {
                        grid: { display: false }
                    },

                    y: {

                        beginAtZero: true,

                        grid: {
                            color: gridColor
                        },

                        ticks: {

                            callback: value =>
                                currency + ' ' +
                                Number(value).toLocaleString()

                        }

                    }

                }

            }

        });

    }

});
</script>

@endsection
