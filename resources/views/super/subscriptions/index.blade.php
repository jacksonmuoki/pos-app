
@extends('super.layout')

@section('title', 'Subscriptions')
@section('breadcrumb', 'Subscription Management')

@section('content')

<style>
    .sub-page { display: flex; flex-direction: column; gap: 22px; }
    .sub-header { display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap; }
    .sub-header h1 { margin:0; font-size:25px; font-weight:800; color:#14213d; }
    .sub-header p { margin:5px 0 0; color:#64748b; font-size:13px; }
    .sub-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 16px; border-radius:9px; background:#128d51; color:white; text-decoration:none; font-size:13px; font-weight:700; border:0; cursor:pointer; }
    .sub-btn:hover { background:#0d7542; color:white; }
    .sub-btn.secondary { background:#f1f5f9; color:#334155; }
    .sub-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:15px; }
    .sub-stat { background:white; border:1px solid #e8edf3; border-radius:13px; padding:19px; }
    .sub-stat span { color:#64748b; font-size:12px; font-weight:600; }
    .sub-stat h2 { margin:10px 0 0; color:#14213d; font-size:27px; font-weight:800; }
    .sub-stat .stat-icon { width:35px; height:35px; display:grid; place-items:center; border-radius:9px; background:#ecfdf3; color:#128d51; margin-bottom:13px; font-weight:800; }
    .sub-card { background:white; border:1px solid #e8edf3; border-radius:13px; overflow:hidden; }
    .sub-card-head { padding:18px 20px; border-bottom:1px solid #edf0f4; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .sub-card-head h3 { margin:0; font-size:15px; font-weight:800; color:#17243d; }
    .sub-filters { display:flex; gap:9px; flex-wrap:wrap; }
    .sub-input { border:1px solid #dce3ec; border-radius:8px; padding:10px 12px; font-size:12px; color:#334155; background:white; outline:none; }
    .sub-input:focus { border-color:#128d51; }
    .sub-table-wrap { overflow-x:auto; }
    .sub-table { width:100%; border-collapse:collapse; min-width:850px; }
    .sub-table th { text-align:left; padding:13px 18px; color:#64748b; background:#f8fafc; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
    .sub-table td { padding:15px 18px; border-top:1px solid #edf0f4; color:#334155; font-size:12px; }
    .sub-company { font-weight:750; color:#17243d; }
    .sub-muted { color:#94a3b8; font-size:11px; margin-top:4px; }
    .sub-status { display:inline-flex; align-items:center; padding:5px 10px; border-radius:30px; font-size:10px; font-weight:800; text-transform:capitalize; }
    .status-active { background:#dcfce7; color:#166534; }
    .status-trial { background:#dbeafe; color:#1d4ed8; }
    .status-pending { background:#fef3c7; color:#92400e; }
    .status-cancelled { background:#fee2e2; color:#991b1b; }
    .status-expired { background:#f1f5f9; color:#475569; }
    .sub-actions { display:flex; gap:7px; align-items:center; }
    .sub-action { padding:7px 10px; border:1px solid #e2e8f0; border-radius:7px; text-decoration:none; color:#334155; font-size:11px; font-weight:700; background:white; }
    .sub-action:hover { border-color:#128d51; color:#128d51; }
    .sub-empty { text-align:center; padding:45px 15px; color:#94a3b8; }
    .sub-empty strong { display:block; color:#334155; margin-bottom:6px; }
    .sub-pagination { padding:16px 20px; }
    @media(max-width:900px) { .sub-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:520px) { .sub-stats { grid-template-columns:1fr 1fr; gap:9px; } .sub-stat { padding:13px; } .sub-stat h2 { font-size:22px; } }
</style>

<div class="sub-page">

    <div class="sub-header">
        <div>
            <h1>Subscriptions</h1>
            <p>Manage company plans, billing periods and subscription status.</p>
        </div>

        <a href="{{ route('super.subscriptions.create') }}" class="sub-btn">
            + Assign Subscription
        </a>
    </div>

    <div class="sub-stats">
        <div class="sub-stat">
            <div class="stat-icon">A</div>
            <span>Total Subscriptions</span>
            <h2>{{ number_format($total) }}</h2>
        </div>

        <div class="sub-stat">
            <div class="stat-icon">✓</div>
            <span>Active Subscriptions</span>
            <h2>{{ number_format($active) }}</h2>
        </div>

        <div class="sub-stat">
            <div class="stat-icon">P</div>
            <span>Pending Payments</span>
            <h2>{{ number_format($pending) }}</h2>
        </div>

        <div class="sub-stat">
            <div class="stat-icon">C</div>
            <span>Cancelled</span>
            <h2>{{ number_format($cancelled) }}</h2>
        </div>
    </div>

    <div class="sub-card">

        <div class="sub-card-head">
            <h3>All Subscriptions</h3>

            <form method="GET" action="{{ route('super.subscriptions.index') }}" class="sub-filters">

                <select name="status" class="sub-input">
                    <option value="">All statuses</option>
                    @foreach(['active', 'trial', 'pending', 'cancelled', 'expired'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="text"
                    name="tenant_id"
                    value="{{ request('tenant_id') }}"
                    class="sub-input"
                    placeholder="Company ID"
                >

                <button type="submit" class="sub-btn">Filter</button>

                <a href="{{ route('super.subscriptions.index') }}" class="sub-btn secondary">Reset</a>
            </form>
        </div>

        <div class="sub-table-wrap">
            <table class="sub-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Billing Cycle</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($subscriptions as $subscription)
                        <tr>
                            <td>
                                <div class="sub-company">
                                    {{ $subscription->tenant_id }}
                                </div>
                                <div class="sub-muted">ID: {{ $subscription->tenant_id }}</div>
                            </td>

                            <td>
                                <strong>{{ $subscription->plan?->name ?? 'Deleted plan' }}</strong>
                            </td>

                            <td>{{ ucfirst($subscription->billing_cycle) }}</td>

                            <td>
                                <strong>
                                    {{ $subscription->currency }}
                                    {{ number_format((float)$subscription->amount, 2) }}
                                </strong>
                            </td>

                            <td>
                                <span class="sub-status status-{{ $subscription->status }}">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>

                            <td>
                                {{ $subscription->starts_at?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                {{ $subscription->ends_at?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                <div class="sub-actions">
                                    <a href="{{ route('super.subscriptions.show', $subscription) }}" class="sub-action">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="sub-empty">
                                    <strong>No subscriptions found</strong>
                                    Start by assigning a plan to a company.
                                    <br><br>
                                    <a href="{{ route('super.subscriptions.create') }}" class="sub-btn">
                                        Assign First Subscription
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sub-pagination">
            {{ $subscriptions->links() }}
        </div>

    </div>

</div>

@endsection
