
@extends('super.layout')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Safe dashboard defaults
    |--------------------------------------------------------------------------
    | Missing controller values will display as zero or empty data.
    */

    $totalCompanies    = (int) ($totalCompanies ?? 0);
    $activeCompanies   = (int) ($activeCompanies ?? 0);
    $suspendedCompanies = (int) ($suspendedCompanies ?? 0);

    $totalUsers        = (int) ($totalUsers ?? 0);
    $totalProducts     = (int) ($totalProducts ?? 0);
    $totalOrders       = (int) ($totalOrders ?? 0);
    $todayOrders       = (int) ($todayOrders ?? 0);
    $lowStockProducts  = (int) ($lowStockProducts ?? 0);
    $pendingCompanies  = (int) ($pendingCompanies ?? 0);

    $totalRevenue      = (float) ($totalRevenue ?? 0);
    $todayRevenue      = (float) ($todayRevenue ?? 0);
    $monthlyRevenueTotal = (float) ($monthlyRevenueTotal ?? 0);

    $recentCompanies = collect($recentCompanies ?? []);

    $activeRate = $totalCompanies > 0
        ? round(($activeCompanies / $totalCompanies) * 100)
        : 0;

    $suspendedRate = $totalCompanies > 0
        ? round(($suspendedCompanies / $totalCompanies) * 100)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Chart data
    |--------------------------------------------------------------------------
    | Expected monthlyRevenue format:
    | ['labels' => ['Jan', 'Feb'], 'values' => [1000, 2000]]
    */

    $monthlyLabels = data_get($monthlyRevenue ?? [], 'labels', [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ]);

    $monthlyValues = data_get($monthlyRevenue ?? [], 'values', array_fill(0, 12, 0));

    $companyPerformance = collect($companyPerformance ?? []);

    $companyChartLabels = $companyPerformance
        ->pluck('name')
        ->values()
        ->all();

    $companyChartValues = $companyPerformance
        ->pluck('revenue')
        ->map(fn ($value) => (float) $value)
        ->values()
        ->all();

    $formatMoney = fn ($amount) => 'KSh ' . number_format((float) $amount, 2);
@endphp

<div class="platform-dashboard">

    {{-- HEADER --}}
    <div class="pd-header">
        <div>
            <span class="pd-eyebrow">PLATFORM CONTROL CENTER</span>
            <h1>Dashboard Overview</h1>
            <p>Monitor your companies, business activity and platform performance.</p>
        </div>

        <div class="pd-header-actions">
            <a href="{{ route('super.activity') }}" class="pd-btn pd-btn-light">
                <i class="fas fa-history"></i>
                Activity Logs
            </a>

            <a href="{{ route('super.companies.create') }}" class="pd-btn pd-btn-primary">
                <i class="fas fa-plus"></i>
                Add Company
            </a>
        </div>
    </div>

    <div class="pd-stats-grid">

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Total Companies</span>
                <div class="pd-stat-icon green"><i class="fas fa-building"></i></div>
            </div>
            <h3>{{ number_format($totalCompanies) }}</h3>
            <small>Registered companies</small>
        </div>

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Active Companies</span>
                <div class="pd-stat-icon blue"><i class="fas fa-check-circle"></i></div>
            </div>
            <h3>{{ number_format($activeCompanies) }}</h3>
            <small>{{ $activeRate }}% of all companies</small>
        </div>

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Suspended Companies</span>
                <div class="pd-stat-icon red"><i class="fas fa-ban"></i></div>
            </div>
            <h3>{{ number_format($suspendedCompanies) }}</h3>
            <small>{{ $suspendedRate }}% of all companies</small>
        </div>

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Pending Companies</span>
                <div class="pd-stat-icon amber"><i class="fas fa-clock"></i></div>
            </div>
            <h3>{{ number_format($pendingCompanies) }}</h3>
            <small>Awaiting attention</small>
        </div>

    </div>

    {{-- BUSINESS STATISTICS --}}
    <div class="pd-section-heading">
        <div>
            <h2>Business Performance</h2>
            <p>Combined figures from all companies, where available.</p>
        </div>
    </div>

    <div class="pd-stats-grid pd-business-grid">

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Total Users</span>
                <div class="pd-stat-icon purple"><i class="fas fa-users"></i></div>
            </div>
            <h3>{{ number_format($totalUsers) }}</h3>
            <small>Users across companies</small>
        </div>

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Total Products</span>
                <div class="pd-stat-icon cyan"><i class="fas fa-boxes"></i></div>
            </div>
            <h3>{{ number_format($totalProducts) }}</h3>
            <small>Products registered</small>
        </div>

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Total Orders</span>
                <div class="pd-stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
            </div>
            <h3>{{ number_format($totalOrders) }}</h3>
            <small>Orders across companies</small>
        </div>

        <div class="pd-stat-card">
            <div class="pd-stat-top">
                <span>Low Stock Items</span>
                <div class="pd-stat-icon amber"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <h3>{{ number_format($lowStockProducts) }}</h3>
            <small>Items requiring attention</small>
        </div>

    </div>

    {{-- REVENUE AND TODAY --}}
    <div class="pd-highlight-grid">

        <div class="pd-highlight-card">
            <div class="pd-highlight-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <span>Total Platform Revenue</span>
                <h2>{{ $formatMoney($totalRevenue) }}</h2>
                <small>Combined company revenue, if supplied</small>
            </div>
        </div>

        <div class="pd-highlight-card">
            <div class="pd-highlight-icon today">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div>
                <span>Today's Revenue</span>
                <h2>{{ $formatMoney($todayRevenue) }}</h2>
                <small>Revenue recorded today, if supplied</small>
            </div>
        </div>

        <div class="pd-highlight-card">
            <div class="pd-highlight-icon orders">
                <i class="fas fa-receipt"></i>
            </div>
            <div>
                <span>Today's Orders</span>
                <h2>{{ number_format($todayOrders) }}</h2>
                <small>Orders recorded today</small>
            </div>
        </div>

    </div>

    {{-- CHARTS --}}
    <div class="pd-section-heading">
        <div>
            <h2>Platform Analytics</h2>
            <p>Revenue trends and company performance.</p>
        </div>
    </div>

    <div class="pd-charts-grid">

        <div class="pd-panel">
            <div class="pd-panel-header">
                <div>
                    <h3>Monthly Revenue</h3>
                    <p>Revenue trend across the platform</p>
                </div>
                <span class="pd-panel-icon"><i class="fas fa-chart-line"></i></span>
            </div>

            <div class="pd-chart-container">
                <canvas id="pdRevenueChart"></canvas>
            </div>
        </div>

        <div class="pd-panel">
            <div class="pd-panel-header">
                <div>
                    <h3>Company Status</h3>
                    <p>Active and suspended companies</p>
                </div>
                <span class="pd-panel-icon"><i class="fas fa-chart-pie"></i></span>
            </div>

            <div class="pd-chart-container pd-doughnut-container">
                <canvas id="pdCompanyChart"></canvas>
            </div>

            <div class="pd-chart-legend">
                <span><i class="legend-dot active"></i> Active ({{ $activeCompanies }})</span>
                <span><i class="legend-dot suspended"></i> Suspended ({{ $suspendedCompanies }})</span>
            </div>
        </div>

        <div class="pd-panel pd-wide-panel">
            <div class="pd-panel-header">
                <div>
                    <h3>Company Revenue Performance</h3>
                    <p>Revenue comparison by company, when available</p>
                </div>
                <span class="pd-panel-icon"><i class="fas fa-chart-bar"></i></span>
            </div>

            <div class="pd-chart-container">
                <canvas id="pdPerformanceChart"></canvas>
            </div>
        </div>

    </div>

    {{-- COMPANY MANAGEMENT SHORTCUTS --}}
    <div class="pd-section-heading">
        <div>
            <h2>Company Management</h2>
            <p>Quick access to the main platform management functions.</p>
        </div>
    </div>

    <div class="pd-management-grid">

        <a href="{{ route('super.companies.create') }}" class="pd-management-card">
            <div class="pd-management-icon green">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div>
                <h3>Register Company</h3>
                <p>Create a new company and configure its account.</p>
            </div>
            <i class="fas fa-arrow-right pd-card-arrow"></i>
        </a>

        <a href="{{ route('super.companies.index') }}" class="pd-management-card">
            <div class="pd-management-icon blue">
                <i class="fas fa-building"></i>
            </div>
            <div>
                <h3>Company Directory</h3>
                <p>View, search and manage registered companies.</p>
            </div>
            <i class="fas fa-arrow-right pd-card-arrow"></i>
        </a>

        <a href="{{ route('super.activity') }}" class="pd-management-card">
            <div class="pd-management-icon purple">
                <i class="fas fa-history"></i>
            </div>
            <div>
                <h3>Activity Logs</h3>
                <p>Review recorded platform activities and changes.</p>
            </div>
            <i class="fas fa-arrow-right pd-card-arrow"></i>
        </a>

    </div>

    {{-- RECENT COMPANIES --}}
    <div class="pd-section-heading pd-recent-heading">
        <div>
            <h2>Recently Registered Companies</h2>
            <p>Latest companies added to your platform.</p>
        </div>

        <a href="{{ route('super.companies.index') }}" class="pd-text-link">
            View all companies <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="pd-panel pd-table-panel">
        <div class="pd-table-wrap">
            <table class="pd-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Company ID</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th class="pd-align-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentCompanies as $company)
                        <tr>
                            <td>
                                <div class="pd-company-cell">
                                    <div class="pd-company-avatar">
                                        {{ strtoupper(substr($company->name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong>{{ $company->name ?? 'Unnamed Company' }}</strong>
                                        <small>Registered company</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="pd-id">#{{ $company->id }}</span>
                            </td>

                            <td>
                                {{ $company->created_at?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                @if($company->suspended ?? false)
                                    <span class="pd-badge suspended">
                                        <i class="fas fa-ban"></i> Suspended
                                    </span>
                                @else
                                    <span class="pd-badge active">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                @endif
                            </td>

                            <td class="pd-align-right">
                                <a href="{{ route('super.companies.show', $company->id) }}"
                                   class="pd-view-btn">
                                    Manage <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="pd-empty">
                                    <i class="fas fa-building"></i>
                                    <strong>No companies registered yet</strong>
                                    <span>Register your first company to get started.</span>
                                    <a href="{{ route('super.companies.create') }}">
                                        Add Company
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

{{-- CHARTS LIBRARY --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const green = '#128d51';
    const muted = '#94a3b8';

    const revenueLabels = @json($monthlyLabels);
    const revenueValues = @json($monthlyValues);

    const companyLabels = ['Active', 'Suspended'];
    const companyValues = [
        @json($activeCompanies),
        @json($suspendedCompanies)
    ];

    const performanceLabels = @json($companyChartLabels);
    const performanceValues = @json($companyChartValues);

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    };

    const revenueCanvas = document.getElementById('pdRevenueChart');

    if (revenueCanvas) {
        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue',
                    data: revenueValues,
                    borderColor: green,
                    backgroundColor: 'rgba(18,141,81,0.10)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'KSh ' + Number(value).toLocaleString()
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    const companyCanvas = document.getElementById('pdCompanyChart');

    if (companyCanvas) {
        new Chart(companyCanvas, {
            type: 'doughnut',
            data: {
                labels: companyLabels,
                datasets: [{
                    data: companyValues,
                    backgroundColor: [green, '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                ...commonOptions,
                cutout: '72%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    const performanceCanvas = document.getElementById('pdPerformanceChart');

    if (performanceCanvas) {
        new Chart(performanceCanvas, {
            type: 'bar',
            data: {
                labels: performanceLabels,
                datasets: [{
                    label: 'Revenue',
                    data: performanceValues,
                    backgroundColor: green,
                    borderRadius: 6,
                    maxBarThickness: 42
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'KSh ' + Number(value).toLocaleString()
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<style>
.platform-dashboard {
    --pd-green: #128d51;
    --pd-green-dark: #0f7545;
    --pd-text: #1e293b;
    --pd-muted: #7b8a9c;
    --pd-border: #e6eaef;
    --pd-bg: #f8fafc;
    width: 100%;
    min-width: 0;
    color: var(--pd-text);
    font-family: 'Manrope', sans-serif;
    box-sizing: border-box;
}

.platform-dashboard *,
.platform-dashboard *::before,
.platform-dashboard *::after {
    box-sizing: border-box;
}

.platform-dashboard .pd-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}

.platform-dashboard .pd-eyebrow {
    color: var(--pd-green);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.2px;
}

.platform-dashboard .pd-header h1 {
    margin: 5px 0;
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -.6px;
}

.platform-dashboard .pd-header p,
.platform-dashboard .pd-section-heading p {
    margin: 0;
    color: var(--pd-muted);
    font-size: 12px;
}

.platform-dashboard .pd-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.platform-dashboard .pd-btn {
    min-height: 40px;
    padding: 0 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    transition: .2s ease;
}

.platform-dashboard .pd-btn-primary {
    color: #fff;
    background: var(--pd-green);
}

.platform-dashboard .pd-btn-primary:hover {
    color: #fff;
    background: var(--pd-green-dark);
}

.platform-dashboard .pd-btn-light {
    color: #475569;
    background: #fff;
    border: 1px solid var(--pd-border);
}

.platform-dashboard .pd-btn-light:hover {
    color: var(--pd-green);
    border-color: var(--pd-green);
}

.platform-dashboard .pd-status {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 15px 17px;
    margin-bottom: 25px;
    background: #f0faf4;
    border: 1px solid #d7efdf;
    border-radius: 10px;
}

.platform-dashboard .pd-status-icon {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: var(--pd-green);
    background: #dcf5e6;
    border-radius: 9px;
}

.platform-dashboard .pd-status-content {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.platform-dashboard .pd-status-content strong {
    font-size: 12px;
}

.platform-dashboard .pd-status-content span,
.platform-dashboard .pd-status-date {
    color: #64748b;
    font-size: 11px;
}

.platform-dashboard .pd-status-date {
    margin-left: auto;
    white-space: nowrap;
}

.platform-dashboard .pd-section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 25px 0 13px;
}

.platform-dashboard .pd-section-heading h2 {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 800;
}

.platform-dashboard .pd-text-link {
    color: var(--pd-green);
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}

.platform-dashboard .pd-text-link:hover {
    color: var(--pd-green-dark);
}

.platform-dashboard .pd-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
}

.platform-dashboard .pd-stat-card {
    min-width: 0;
    padding: 16px;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 11px;
    box-shadow: 0 2px 7px rgba(15, 23, 42, .025);
}

.platform-dashboard .pd-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.platform-dashboard .pd-stat-top > span {
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
}

.platform-dashboard .pd-stat-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 8px;
    font-size: 13px;
}

.platform-dashboard .pd-stat-icon.green,
.platform-dashboard .pd-management-icon.green {
    color: #128d51;
    background: #e8f7ee;
}

.platform-dashboard .pd-stat-icon.blue,
.platform-dashboard .pd-management-icon.blue {
    color: #2563eb;
    background: #eff6ff;
}

.platform-dashboard .pd-stat-icon.red {
    color: #dc2626;
    background: #fef2f2;
}

.platform-dashboard .pd-stat-icon.amber {
    color: #d97706;
    background: #fffbeb;
}

.platform-dashboard .pd-stat-icon.purple,
.platform-dashboard .pd-management-icon.purple {
    color: #7c3aed;
    background: #f5f3ff;
}

.platform-dashboard .pd-stat-icon.cyan {
    color: #0891b2;
    background: #ecfeff;
}

.platform-dashboard .pd-stat-card h3 {
    margin: 12px 0 3px;
    font-size: 25px;
    line-height: 1.2;
    font-weight: 800;
    letter-spacing: -.6px;
    overflow-wrap: anywhere;
}

.platform-dashboard .pd-stat-card small {
    color: var(--pd-muted);
    font-size: 10px;
}

.platform-dashboard .pd-highlight-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
    margin-top: 14px;
}

.platform-dashboard .pd-highlight-card {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    padding: 17px;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 11px;
}

.platform-dashboard .pd-highlight-icon {
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: var(--pd-green);
    background: #e8f7ee;
    border-radius: 9px;
}

.platform-dashboard .pd-highlight-icon.today {
    color: #2563eb;
    background: #eff6ff;
}

.platform-dashboard .pd-highlight-icon.orders {
    color: #7c3aed;
    background: #f5f3ff;
}

.platform-dashboard .pd-highlight-card span {
    color: #64748b;
    font-size: 10px;
}

.platform-dashboard .pd-highlight-card h2 {
    margin: 4px 0;
    font-size: 19px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.platform-dashboard .pd-highlight-card small {
    color: var(--pd-muted);
    font-size: 9px;
}

.platform-dashboard .pd-charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(260px, 1fr);
    gap: 14px;
}

.platform-dashboard .pd-panel {
    min-width: 0;
    padding: 17px;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 11px;
    box-shadow: 0 2px 7px rgba(15, 23, 42, .025);
}

.platform-dashboard .pd-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.platform-dashboard .pd-panel-header h3 {
    margin: 0 0 4px;
    font-size: 13px;
    font-weight: 800;
}

.platform-dashboard .pd-panel-header p {
    margin: 0;
    color: var(--pd-muted);
    font-size: 10px;
}

.platform-dashboard .pd-panel-icon {
    color: var(--pd-green);
    font-size: 15px;
}

.platform-dashboard .pd-chart-container {
    position: relative;
    width: 100%;
    height: 235px;
}

.platform-dashboard .pd-doughnut-container {
    max-width: 240px;
    height: 190px;
    margin: 0 auto;
}

.platform-dashboard .pd-chart-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-top: 8px;
    color: #64748b;
    font-size: 10px;
}

.platform-dashboard .legend-dot {
    width: 8px;
    height: 8px;
    display: inline-block;
    margin-right: 4px;
    border-radius: 50%;
}

.platform-dashboard .legend-dot.active {
    background: var(--pd-green);
}

.platform-dashboard .legend-dot.suspended {
    background: #ef4444;
}

.platform-dashboard .pd-wide-panel {
    grid-column: 1 / -1;
}

.platform-dashboard .pd-management-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
}

.platform-dashboard .pd-management-card {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    padding: 15px;
    background: #fff;
    border: 1px solid var(--pd-border);
    border-radius: 10px;
    text-decoration: none;
    transition: .2s ease;
}

.platform-dashboard .pd-management-card:hover {
    border-color: #b7dfc8;
    box-shadow: 0 5px 15px rgba(18, 141, 81, .07);
    transform: translateY(-2px);
}

.platform-dashboard .pd-management-icon {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 9px;
}

.platform-dashboard .pd-management-card h3 {
    margin: 0 0 4px;
    color: var(--pd-text);
    font-size: 11px;
    font-weight: 800;
}

.platform-dashboard .pd-management-card p {
    margin: 0;
    color: var(--pd-muted);
    font-size: 9px;
    line-height: 1.5;
}

.platform-dashboard .pd-card-arrow {
    margin-left: auto;
    color: #94a3b8;
    font-size: 11px;
}

.platform-dashboard .pd-table-panel {
    padding: 0;
    overflow: hidden;
}

.platform-dashboard .pd-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.platform-dashboard .pd-table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
    text-align: left;
}

.platform-dashboard .pd-table th {
    padding: 13px 16px;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid var(--pd-border);
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.platform-dashboard .pd-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #f0f2f5;
    color: #475569;
    font-size: 11px;
    white-space: nowrap;
}

.platform-dashboard .pd-table tbody tr:last-child td {
    border-bottom: 0;
}

.platform-dashboard .pd-company-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.platform-dashboard .pd-company-avatar {
    width: 33px;
    height: 33px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    color: var(--pd-green);
    background: #e8f7ee;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
}

.platform-dashboard .pd-company-cell strong {
    display: block;
    color: var(--pd-text);
    font-size: 11px;
}

.platform-dashboard .pd-company-cell small {
    display: block;
    margin-top: 3px;
    color: var(--pd-muted);
    font-size: 9px;
}

.platform-dashboard .pd-id {
    color: #64748b;
    font-weight: 700;
}

.platform-dashboard .pd-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 800;
}

.platform-dashboard .pd-badge.active {
    color: #15803d;
    background: #ecfdf3;
}

.platform-dashboard .pd-badge.suspended {
    color: #b42318;
    background: #fef3f2;
}

.platform-dashboard .pd-align-right {
    text-align: right;
}

.platform-dashboard .pd-view-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    color: var(--pd-green);
    background: #f0faf4;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 800;
    text-decoration: none;
}

.platform-dashboard .pd-view-btn:hover {
    color: #fff;
    background: var(--pd-green);
}

.platform-dashboard .pd-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;
    padding: 35px 15px;
    text-align: center;
}

.platform-dashboard .pd-empty > i {
    margin-bottom: 5px;
    color: #94a3b8;
    font-size: 25px;
}

.platform-dashboard .pd-empty strong {
    font-size: 12px;
}

.platform-dashboard .pd-empty span {
    color: var(--pd-muted);
    font-size: 10px;
}

.platform-dashboard .pd-empty a {
    margin-top: 7px;
    color: var(--pd-green);
    font-size: 11px;
    font-weight: 800;
    text-decoration: none;
}

@media (max-width: 1100px) {
    .platform-dashboard .pd-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .platform-dashboard .pd-management-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .platform-dashboard .pd-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-dashboard .pd-header-actions {
        width: 100%;
    }

    .platform-dashboard .pd-header-actions .pd-btn {
        flex: 1;
    }

    .platform-dashboard .pd-highlight-grid {
        grid-template-columns: 1fr;
    }

    .platform-dashboard .pd-charts-grid {
        grid-template-columns: 1fr;
    }

    .platform-dashboard .pd-wide-panel {
        grid-column: auto;
    }

    .platform-dashboard .pd-management-grid {
        grid-template-columns: 1fr;
    }

    .platform-dashboard .pd-status {
        align-items: flex-start;
    }

    .platform-dashboard .pd-status-date {
        display: none;
    }
}

@media (max-width: 480px) {
    .platform-dashboard .pd-header h1 {
        font-size: 20px;
    }

    .platform-dashboard .pd-stats-grid {
        gap: 9px;
    }

    .platform-dashboard .pd-stat-card {
        padding: 12px;
    }

    .platform-dashboard .pd-stat-card h3 {
        font-size: 21px;
    }

    .platform-dashboard .pd-section-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .platform-dashboard .pd-section-heading .pd-text-link {
        align-self: flex-start;
    }

    .platform-dashboard .pd-chart-container {
        height: 210px;
    }
}

@media (max-width: 340px) {
    .platform-dashboard .pd-stats-grid {
        grid-template-columns: 1fr;
    }

    .platform-dashboard .pd-header-actions {
        flex-direction: column;
    }

    .platform-dashboard .pd-header-actions .pd-btn {
        width: 100%;
    }
}
</style>
@endsection
