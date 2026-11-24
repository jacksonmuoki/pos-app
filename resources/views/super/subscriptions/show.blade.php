
@extends('super.layout')

@section('title', 'Subscription Details')
@section('breadcrumb', 'Subscription Details')

@section('content')

<style>
    .detail-wrap { display:flex; flex-direction:column; gap:20px; }
    .detail-header { display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap; }
    .detail-header h1 { margin:0; font-size:25px; font-weight:800; color:#14213d; }
    .detail-header p { margin:5px 0 0; font-size:12px; color:#64748b; }
    .detail-actions { display:flex; gap:9px; flex-wrap:wrap; }
    .detail-btn { border:0; border-radius:8px; padding:11px 15px; background:#128d51; color:white; text-decoration:none; font-size:12px; font-weight:750; cursor:pointer; }
    .detail-btn.back { background:#f1f5f9; color:#334155; }
    .detail-btn.danger { background:#dc2626; }
    .detail-grid { display:grid; grid-template-columns:1.3fr .7fr; gap:18px; }
    .detail-card { background:white; border:1px solid #e8edf3; border-radius:13px; padding:22px; }
    .detail-card h3 { font-size:15px; color:#17243d; font-weight:800; margin:0 0 20px; }
    .detail-info { display:grid; grid-template-columns:1fr 1fr; gap:22px 15px; }
    .detail-info span { display:block; font-size:11px; color:#94a3b8; margin-bottom:6px; }
    .detail-info strong { color:#334155; font-size:13px; overflow-wrap:anywhere; }
    .detail-price { font-size:29px; font-weight:850; color:#128d51; margin:10px 0; }
    .detail-status { display:inline-block; border-radius:30px; padding:6px 12px; font-size:11px; font-weight:800; background:#f1f5f9; color:#334155; }
    .detail-status.active { background:#dcfce7; color:#166534; }
    .detail-status.trial { background:#dbeafe; color:#1d4ed8; }
    .detail-status.pending { background:#fef3c7; color:#92400e; }
    .detail-status.cancelled { background:#fee2e2; color:#991b1b; }
    .detail-warning { background:#fff7ed; border:1px solid #fed7aa; padding:17px; border-radius:10px; }
    .detail-warning strong { display:block; font-size:13px; color:#9a3412; margin-bottom:5px; }
    .detail-warning p { font-size:12px; color:#9a3412; margin:0; }
    .detail-empty { color:#94a3b8; font-size:12px; }
    @media(max-width:750px) { .detail-grid { grid-template-columns:1fr; } }
    @media(max-width:480px) { .detail-info { grid-template-columns:1fr; } }
</style>

<div class="detail-wrap">

    <div class="detail-header">
        <div>
            <h1>Subscription Details</h1>
            <p>Subscription #{{ $subscription->id }}</p>
        </div>

        <div class="detail-actions">
            <a href="{{ route('super.subscriptions.index') }}" class="detail-btn back">
                Back to Subscriptions
            </a>
        </div>
    </div>

    @if(session('status'))
        <div style="padding:13px;border-radius:8px;background:#ecfdf3;color:#166534;font-size:12px;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div style="padding:13px;border-radius:8px;background:#fef2f2;color:#991b1b;font-size:12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="detail-grid">

        <div class="detail-card">
            <h3>Subscription Information</h3>

            <div class="detail-info">

                <div>
                    <span>Company</span>
                    <strong>{{ $tenant?->name ?? $subscription->tenant_id }}</strong>
                </div>

                <div>
                    <span>Company ID</span>
                    <strong>{{ $subscription->tenant_id }}</strong>
                </div>

                <div>
                    <span>Pricing Plan</span>
                    <strong>{{ $subscription->plan?->name ?? 'Deleted plan' }}</strong>
                </div>

                <div>
                    <span>Billing Cycle</span>
                    <strong>{{ ucfirst($subscription->billing_cycle) }}</strong>
                </div>

                <div>
                    <span>Subscription Status</span>
                    <strong>
                        <span class="detail-status {{ $subscription->status }}">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </strong>
                </div>

                <div>
                    <span>Auto Renewal</span>
                    <strong>{{ $subscription->auto_renew ? 'Enabled' : 'Disabled' }}</strong>
                </div>

                <div>
                    <span>Start Date</span>
                    <strong>{{ $subscription->starts_at?->format('d M Y, h:i A') ?? 'Not started' }}</strong>
                </div>

                <div>
                    <span>End Date</span>
                    <strong>{{ $subscription->ends_at?->format('d M Y, h:i A') ?? 'Not set' }}</strong>
                </div>

                <div>
                    <span>Trial Ends</span>
                    <strong>{{ $subscription->trial_ends_at?->format('d M Y, h:i A') ?? 'Not applicable' }}</strong>
                </div>

                <div>
                    <span>Cancelled At</span>
                    <strong>{{ $subscription->cancelled_at?->format('d M Y, h:i A') ?? 'Not cancelled' }}</strong>
                </div>

                <div>
                    <span>Created At</span>
                    <strong>{{ $subscription->created_at?->format('d M Y, h:i A') }}</strong>
                </div>

            </div>
        </div>

        <div class="detail-card">
            <h3>Billing Summary</h3>

            <span style="font-size:11px;color:#94a3b8;">Subscription Amount</span>

            <div class="detail-price">
                {{ $subscription->currency }}
                {{ number_format((float)$subscription->amount, 2) }}
            </div>

            <p style="font-size:12px;color:#64748b;">
                {{ ucfirst($subscription->billing_cycle) }} billing
            </p>

            <hr style="border:0;border-top:1px solid #edf0f4;margin:20px 0;">

            <span style="font-size:11px;color:#94a3b8;">Current Plan</span>

            <h3 style="margin:7px 0;">
                {{ $subscription->plan?->name ?? 'Deleted plan' }}
            </h3>

            @if($subscription->plan)
                <p style="font-size:12px;color:#64748b;line-height:1.8;">
                    {{ $subscription->plan->description }}
                </p>
            @endif
        </div>

    </div>

    @if(!in_array($subscription->status, ['cancelled', 'expired'], true))
        <div class="detail-warning">
            <strong>Cancel Subscription</strong>
            <p>Cancellation will change the subscription status to cancelled and disable auto-renewal.</p>

            <form
                method="POST"
                action="{{ route('super.subscriptions.cancel', $subscription) }}"
                onsubmit="return confirm('Are you sure you want to cancel this subscription?')"
                style="margin-top:15px;"
            >
                @csrf

                <div style="margin-bottom:12px;">
                    <input
                        type="text"
                        name="reason"
                        maxlength="500"
                        placeholder="Reason for cancellation (optional)"
                        style="width:100%;max-width:450px;padding:11px;border:1px solid #fed7aa;border-radius:7px;font-size:12px;"
                    >
                </div>

                <button type="submit" class="detail-btn danger">
                    Cancel Subscription
                </button>
            </form>
        </div>
    @endif

</div>

@endsection
