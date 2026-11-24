
@extends('super.layout')

@section('title', 'Assign Subscription')
@section('breadcrumb', 'Assign Subscription')

@section('content')

<style>
    .assign-wrap { max-width:900px; margin:auto; }
    .assign-heading { margin-bottom:22px; }
    .assign-heading h1 { margin:0; font-size:25px; font-weight:800; color:#14213d; }
    .assign-heading p { margin:6px 0 0; font-size:13px; color:#64748b; }
    .assign-card { background:white; border:1px solid #e8edf3; border-radius:14px; padding:25px; }
    .assign-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .assign-field { margin-bottom:19px; }
    .assign-field label { display:block; margin-bottom:8px; color:#334155; font-size:12px; font-weight:750; }
    .assign-field input, .assign-field select { width:100%; padding:12px 13px; border:1px solid #dce3ec; border-radius:8px; outline:none; background:white; color:#334155; font-size:13px; }
    .assign-field input:focus, .assign-field select:focus { border-color:#128d51; }
    .assign-help { margin-top:6px; font-size:11px; color:#94a3b8; }
    .assign-trial { display:flex; align-items:flex-start; gap:10px; padding:15px; border-radius:9px; background:#f0fdf4; border:1px solid #bbf7d0; margin-bottom:20px; }
    .assign-trial input { margin-top:3px; accent-color:#128d51; }
    .assign-trial label { font-size:12px; color:#166534; font-weight:700; }
    .assign-trial small { display:block; font-weight:400; margin-top:4px; }
    .assign-actions { display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #edf0f4; padding-top:20px; }
    .assign-btn { border:0; border-radius:8px; padding:12px 18px; background:#128d51; color:white; font-size:12px; font-weight:750; text-decoration:none; cursor:pointer; }
    .assign-btn:hover { background:#0d7542; color:white; }
    .assign-btn.cancel { background:#f1f5f9; color:#334155; }
    .assign-error { color:#dc2626; font-size:11px; margin-top:5px; }
    @media(max-width:650px) { .assign-grid { grid-template-columns:1fr; gap:0; } .assign-card { padding:17px; } }
</style>

<div class="assign-wrap">

    <div class="assign-heading">
        <h1>Assign Subscription</h1>
        <p>Choose a company and assign it a SaaS pricing plan.</p>
    </div>

    <div class="assign-card">

        @if($errors->any())
            <div style="background:#fef2f2;color:#991b1b;padding:13px;border-radius:8px;margin-bottom:20px;font-size:12px;">
                <strong>Please correct the following:</strong>
                <ul style="margin:8px 0 0;padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('super.subscriptions.store') }}">
            @csrf

            <div class="assign-grid">

                <div class="assign-field">
                    <label for="tenant_id">Select Company *</label>

                    <select name="tenant_id" id="tenant_id" required>
                        <option value="">Choose company</option>

                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->name ?: $tenant->id }} ({{ $tenant->id }})
                            </option>
                        @endforeach
                    </select>

                    @error('tenant_id')
                        <div class="assign-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="assign-field">
                    <label for="plan_id">Select Pricing Plan *</label>

                    <select name="plan_id" id="plan_id" required>
                        <option value="">Choose plan</option>

                        @foreach($plans as $plan)
                            <option
                                value="{{ $plan->id }}"
                                data-monthly="{{ $plan->monthly_price }}"
                                data-quarterly="{{ $plan->quarterly_price }}"
                                data-annual="{{ $plan->annual_price }}"
                                data-currency="{{ $plan->currency }}"
                                data-trial="{{ $plan->trial_days }}"
                                @selected(old('plan_id') == $plan->id)
                            >
                                {{ $plan->name }} — {{ $plan->currency }} {{ number_format((float)$plan->monthly_price, 2) }}/month
                            </option>
                        @endforeach
                    </select>

                    @error('plan_id')
                        <div class="assign-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="assign-field">
                    <label for="billing_cycle">Billing Cycle *</label>

                    <select name="billing_cycle" id="billing_cycle" required>
                        <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Monthly</option>
                        <option value="quarterly" @selected(old('billing_cycle') === 'quarterly')>Quarterly</option>
                        <option value="annual" @selected(old('billing_cycle') === 'annual')>Annually</option>
                    </select>

                    @error('billing_cycle')
                        <div class="assign-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="assign-field">
                    <label>Subscription Amount</label>
                    <input type="text" id="display_amount" readonly placeholder="Select a plan">
                    <div class="assign-help">Automatically calculated from the selected plan and billing cycle.</div>
                </div>

            </div>

            <div class="assign-trial">
                <input
                    type="checkbox"
                    name="start_trial"
                    value="1"
                    id="start_trial"
                    @checked(old('start_trial'))
                >

                <label for="start_trial">
                    Start with a free trial
                    <small id="trial_message">Trial availability depends on the selected plan.</small>
                </label>
            </div>

            <div class="assign-actions">
                <a href="{{ route('super.subscriptions.index') }}" class="assign-btn cancel">
                    Cancel
                </a>

                <button type="submit" class="assign-btn">
                    Create Subscription
                </button>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const plan = document.getElementById('plan_id');
    const cycle = document.getElementById('billing_cycle');
    const amount = document.getElementById('display_amount');
    const trial = document.getElementById('start_trial');
    const trialMessage = document.getElementById('trial_message');

    function updateDetails() {
        const selected = plan.options[plan.selectedIndex];

        if (!selected || !selected.value) {
            amount.value = '';
            trial.disabled = true;
            trial.checked = false;
            trialMessage.textContent = 'Select a plan to see trial availability.';
            return;
        }

        const currency = selected.dataset.currency || 'KES';
        const price = selected.dataset[cycle.value];
        const trialDays = Number(selected.dataset.trial || 0);

        amount.value = currency + ' ' + Number(price || 0).toLocaleString(
            'en-KE',
            {minimumFractionDigits: 2, maximumFractionDigits: 2}
        );

        trial.disabled = trialDays < 1;

        if (trialDays < 1) {
            trial.checked = false;
            trialMessage.textContent = 'This plan does not offer a free trial.';
        } else {
            trialMessage.textContent = trialDays + ' free trial days are available.';
        }
    }

    plan.addEventListener('change', updateDetails);
    cycle.addEventListener('change', updateDetails);

    updateDetails();
});
</script>

@endsection
