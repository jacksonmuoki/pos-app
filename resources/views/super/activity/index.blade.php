
@extends('super.layout')

@section('title', 'Activity Logs')
@section('breadcrumb', 'Activity Logs')

@section('content')

<style>
    .activity-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .activity-stat {
        background: #fff;
        border: 1px solid #e6eaef;
        border-radius: 12px;
        padding: 19px;
        box-shadow: 0 1px 3px rgba(0,0,0,.03);
    }

    .activity-stat-label {
        color: #7b8a9c;
        font-size: 12px;
        font-weight: 600;
    }

    .activity-stat-value {
        display: block;
        margin-top: 9px;
        font-size: 25px;
        font-weight: 800;
        color: #1e293b;
        font-family: 'Manrope', sans-serif;
    }

    .activity-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        padding: 20px;
        border-bottom: 1px solid #e6eaef;
    }

    .activity-toolbar h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
    }

    .activity-toolbar p {
        color: #7b8a9c;
        margin: 5px 0 0;
        font-size: 12px;
    }

    .activity-filters {
        display: grid;
        grid-template-columns: minmax(180px, 1.5fr) repeat(2, minmax(150px, 1fr)) auto;
        gap: 10px;
        width: 100%;
        padding: 18px 20px;
        background: #fafbfd;
        border-bottom: 1px solid #e6eaef;
    }

    .activity-filters input,
    .activity-filters select {
        width: 100%;
        min-width: 0;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #e1e6ed;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        outline: none;
        font-size: 12px;
    }

    .activity-filters input:focus,
    .activity-filters select:focus {
        border-color: #64748b;
    }

    .activity-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .activity-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px;
    }

    .activity-table th {
        background: #fafbfd;
        color: #7b8a9c;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .7px;
        font-weight: 800;
        padding: 14px 18px;
        text-align: left;
        white-space: nowrap;
    }

    .activity-table td {
        padding: 16px 18px;
        border-top: 1px solid #f0f3f7;
        vertical-align: middle;
        font-size: 12px;
    }

    .activity-table tbody tr:hover {
        background: #fafbfd;
    }

    .activity-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #edf2f7;
        color: #334155;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .activity-admin {
        font-weight: 700;
        color: #1e293b;
    }

    .activity-company {
        color: #334155;
        font-weight: 700;
    }

    .activity-muted {
        color: #94a3b8;
    }

    .activity-details {
        max-width: 250px;
        color: #64748b;
        overflow-wrap: anywhere;
        line-height: 1.6;
    }

    .activity-ip {
        color: #64748b;
        font-family: monospace;
        font-size: 11px;
    }

    .activity-empty {
        padding: 55px 20px;
        text-align: center;
        color: #7b8a9c;
    }

    .activity-empty strong {
        display: block;
        color: #334155;
        font-size: 14px;
        margin-bottom: 6px;
    }

    .activity-pagination {
        padding: 18px 20px;
        border-top: 1px solid #e6eaef;
    }

    .activity-pagination nav > div:first-child {
        font-size: 12px;
        color: #7b8a9c;
    }

    .activity-pagination svg {
        width: 17px;
        height: 17px;
    }

    @media(max-width: 850px) {
        .activity-summary {
            grid-template-columns: 1fr;
        }

        .activity-filters {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media(max-width: 520px) {
        .activity-filters {
            grid-template-columns: 1fr;
        }

        .activity-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

@php
    $totalActivities = $activities->total();
    $currentPageActivities = $activities->count();
    $companyCount = $companies->count();
@endphp

<div class="page-heading">
    <div>
        <h1>Activity Logs</h1>
        <p>Monitor administrative actions and company events across your platform.</p>
    </div>
</div>

<div class="activity-summary">
    <div class="activity-stat">
        <span class="activity-stat-label">Matching activities</span>
        <strong class="activity-stat-value">
            {{ number_format($totalActivities) }}
        </strong>
    </div>

    <div class="activity-stat">
        <span class="activity-stat-label">Companies represented</span>
        <strong class="activity-stat-value">
            {{ number_format($companyCount) }}
        </strong>
    </div>

    <div class="activity-stat">
        <span class="activity-stat-label">Activities on this page</span>
        <strong class="activity-stat-value">
            {{ number_format($currentPageActivities) }}
        </strong>
    </div>
</div>

<div class="panel">
    <div class="activity-toolbar">
        <div>
            <h2>Platform activity</h2>
            <p>Review and filter recorded administrative events.</p>
        </div>

        <span class="activity-muted" style="font-size:12px">
            Latest events first
        </span>
    </div>

    <form method="GET" action="{{ route('super.activity') }}" class="activity-filters">

        <input
            type="search"
            name="search"
            placeholder="Search admin, action, company or IP..."
            value="{{ request('search') }}"
        >

        <select name="action">
            <option value="">All activity types</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>
                    {{ ucwords(str_replace(['.', '_'], ' ', $action)) }}
                </option>
            @endforeach
        </select>

        <select name="tenant_id">
            <option value="">All companies</option>
            @foreach($companies as $company)
                <option value="{{ $company }}" @selected(request('tenant_id') === $company)>
                    {{ $company }}
                </option>
            @endforeach
        </select>

        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('super.activity') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

    <div class="activity-table-wrap">
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Administrator</th>
                    <th>Company</th>
                    <th>Details</th>
                    <th>IP address</th>
                    <th>Date & time</th>
                </tr>
            </thead>

            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td>
                            <span class="activity-action">
                                {{ ucwords(str_replace(['.', '_'], ' ', $activity->action)) }}
                            </span>
                        </td>

                        <td>
                            <span class="activity-admin">
                                {{ $activity->super_admin_name ?: 'System' }}
                            </span>
                        </td>

                        <td>
                            @if($activity->tenant_id)
                                <span class="activity-company">
                                    {{ $activity->tenant_id }}
                                </span>
                            @else
                                <span class="activity-muted">Platform</span>
                            @endif
                        </td>

                        <td>
                            <div class="activity-details">
                                @if($activity->details)
                                    {{ json_encode($activity->details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                @else
                                    <span class="activity-muted">No additional details</span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <span class="activity-ip">
                                {{ $activity->ip ?: '—' }}
                            </span>
                        </td>

                        <td>
                            @if($activity->created_at)
                                <div style="font-weight:700;color:#334155">
                                    {{ $activity->created_at->format('d M Y') }}
                                </div>
                                <div class="activity-muted" style="margin-top:4px">
                                    {{ $activity->created_at->format('h:i A') }}
                                </div>
                            @else
                                <span class="activity-muted">Unknown</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="activity-empty">
                                <strong>No activity found</strong>
                                Try changing your search or filters.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($activities->hasPages())
        <div class="activity-pagination">
            {{ $activities->links() }}
        </div>
    @endif
</div>

@endsection

