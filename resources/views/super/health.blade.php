
@extends('super.layout')

@section('content')

@php
    $problems = collect($checks)->where('ok', false)->count()
        + collect($tenantRows)->where('ok', false)->count();

    $platformTotal = count($checks);
    $platformHealthy = collect($checks)->where('ok', true)->count();

    $companyTotal = count($tenantRows);
    $companyHealthy = collect($tenantRows)->where('ok', true)->count();

    $totalChecks = $platformTotal + $companyTotal;
    $healthyChecks = $platformHealthy + $companyHealthy;
@endphp

<style>
    .system-health-page {
        --health-primary: #128d51;
        --health-primary-dark: #0f7545;
        --health-text: #1e293b;
        --health-muted: #7b8a9c;
        --health-border: #e6eaef;
        --health-bg: #f8fafc;
        font-family: 'Manrope', sans-serif;
        color: var(--health-text);
    }

    .system-health-page * {
        box-sizing: border-box;
    }

    .system-health-page .health-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 23px;
    }

    .system-health-page .health-header h1 {
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.7px;
        margin: 0 0 6px;
    }

    .system-health-page .health-header p {
        font-size: 12px;
        color: var(--health-muted);
        margin: 0;
    }

    .system-health-page .overall-status {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 10px 14px;
        border: 1px solid #d1f0dc;
        border-radius: 9px;
        background: #effaf3;
        color: var(--health-primary);
        font-size: 11px;
        font-weight: 800;
    }

    .system-health-page .overall-status.problem {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #b42318;
    }

    .system-health-page .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        flex-shrink: 0;
    }

    .system-health-page .health-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .system-health-page .health-stat {
        background: #fff;
        border: 1px solid var(--health-border);
        border-radius: 11px;
        padding: 19px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    .system-health-page .health-stat-label {
        color: var(--health-muted);
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .system-health-page .health-stat-value {
        font-size: 25px;
        font-weight: 800;
        letter-spacing: -.7px;
        color: var(--health-text);
    }

    .system-health-page .health-stat-note {
        color: var(--health-muted);
        font-size: 10px;
        margin-top: 5px;
    }

    .system-health-page .health-stat-value.good {
        color: var(--health-primary);
    }

    .system-health-page .health-stat-value.bad {
        color: #b42318;
    }

    .system-health-page .health-section {
        background: #fff;
        border: 1px solid var(--health-border);
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 21px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    .system-health-page .health-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding-bottom: 15px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--health-border);
    }

    .system-health-page .health-section-header h2 {
        font-size: 14px;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .system-health-page .health-section-header h2::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 5px;
        background: var(--health-primary);
    }

    .system-health-page .health-section-header span {
        font-size: 11px;
        color: var(--health-muted);
    }

    .system-health-page .health-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .system-health-page table {
        width: 100%;
        min-width: 600px;
        border-collapse: collapse;
        text-align: left;
    }

    .system-health-page table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 13px 14px;
        border-bottom: 1px solid var(--health-border);
        white-space: nowrap;
    }

    .system-health-page table td {
        padding: 14px;
        border-bottom: 1px solid #edf0f3;
        font-size: 11px;
        color: #475569;
        vertical-align: middle;
    }

    .system-health-page table tbody tr:last-child td {
        border-bottom: 0;
    }

    .system-health-page table tbody tr:hover {
        background: #fafcfb;
    }

    .system-health-page .health-check-name {
        font-weight: 700;
        color: var(--health-text);
    }

    .system-health-page .health-detail {
        color: var(--health-muted);
        line-height: 1.6;
        max-width: 450px;
        overflow-wrap: anywhere;
    }

    .system-health-page .health-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .system-health-page .health-badge.good {
        background: #ecfdf3;
        color: #128d51;
    }

    .system-health-page .health-badge.bad {
        background: #fff1f2;
        color: #b42318;
    }

    .system-health-page .health-empty {
        text-align: center;
        padding: 28px 15px !important;
        color: var(--health-muted) !important;
    }

    .system-health-page .health-progress {
        height: 7px;
        background: #edf1f5;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 14px;
    }

    .system-health-page .health-progress-bar {
        height: 100%;
        background: var(--health-primary);
        border-radius: 10px;
        transition: width .3s ease;
    }

    .system-health-page .health-progress-bar.problem {
        background: #dc2626;
    }

    @media (max-width: 1000px) {
        .system-health-page .health-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .system-health-page .health-header h1 {
            font-size: 20px;
        }

        .system-health-page .health-summary {
            gap: 10px;
        }

        .system-health-page .health-stat {
            padding: 15px;
        }

        .system-health-page .health-stat-value {
            font-size: 21px;
        }

        .system-health-page .health-section {
            padding: 16px;
        }

        .system-health-page .health-section-header {
            align-items: flex-start;
        }
    }
</style>

<div class="system-health-page">

    {{-- Page heading --}}
    <div class="health-header">
        <div>
            <h1>System Health</h1>
            <p>Monitor platform infrastructure, company databases and system availability.</p>
        </div>

        <div class="overall-status {{ $problems ? 'problem' : '' }}">
            <span class="status-indicator"></span>
            {{ $problems ? $problems . ' item(s) need attention' : 'All Systems Healthy' }}
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="health-summary">

        <div class="health-stat">
            <div class="health-stat-label">Overall Checks</div>
            <div class="health-stat-value">{{ $totalChecks }}</div>
            <div class="health-stat-note">Platform and company checks</div>
        </div>

        <div class="health-stat">
            <div class="health-stat-label">Healthy Checks</div>
            <div class="health-stat-value good">{{ $healthyChecks }}</div>
            <div class="health-stat-note">Checks passing successfully</div>
        </div>

        <div class="health-stat">
            <div class="health-stat-label">Issues Detected</div>
            <div class="health-stat-value {{ $problems ? 'bad' : 'good' }}">
                {{ $problems }}
            </div>
            <div class="health-stat-note">Items requiring attention</div>
        </div>

        <div class="health-stat">
            <div class="health-stat-label">Companies</div>
            <div class="health-stat-value">{{ $companyTotal }}</div>
            <div class="health-stat-note">
                {{ $companyHealthy }} passing company checks
            </div>
        </div>

    </div>

    {{-- Health progress --}}
    <section class="health-section">
        <div class="health-section-header">
            <h2>System Health Overview</h2>
            <span>
                {{ $totalChecks ? round(($healthyChecks / $totalChecks) * 100) : 0 }}% healthy
            </span>
        </div>

        @php
            $healthPercentage = $totalChecks
                ? round(($healthyChecks / $totalChecks) * 100)
                : 0;
        @endphp

        <div class="health-progress">
            <div
                class="health-progress-bar {{ $problems ? 'problem' : '' }}"
                style="width: {{ $healthPercentage }}%">
            </div>
        </div>
    </section>

    {{-- Platform checks --}}
    <section class="health-section">

        <div class="health-section-header">
            <h2>Platform Infrastructure</h2>
            <span>{{ $platformHealthy }} / {{ $platformTotal }} healthy</span>
        </div>

        <div class="health-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>System Check</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($checks as $c)
                        <tr>
                            <td>
                                <span class="health-check-name">
                                    {{ $c['name'] }}
                                </span>
                            </td>

                            <td>
                                @if ($c['ok'])
                                    <span class="health-badge good">● Healthy</span>
                                @else
                                    <span class="health-badge bad">● Problem</span>
                                @endif
                            </td>

                            <td>
                                <div class="health-detail">
                                    {{ $c['detail'] }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="health-empty">
                                No platform health checks available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    {{-- Company checks --}}
    <section class="health-section">

        <div class="health-section-header">
            <h2>Company Database Health</h2>
            <span>{{ $companyHealthy }} / {{ $companyTotal }} healthy</span>
        </div>

        <div class="health-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Database</th>
                        <th>Migrations</th>
                        <th>Last Backup</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($tenantRows as $t)
                        <tr>
                            <td>
                                <span class="health-check-name">
                                    {{ $t['id'] }}
                                </span>
                            </td>

                            <td>{{ $t['db'] }}</td>

                            <td>{{ $t['migrations'] }}</td>

                            <td>{{ $t['backup'] }}</td>

                            <td>
                                @if ($t['ok'])
                                    <span class="health-badge good">● Healthy</span>
                                @else
                                    <span class="health-badge bad">● Attention</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="health-empty">
                                No companies registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

</div>

@endsection
