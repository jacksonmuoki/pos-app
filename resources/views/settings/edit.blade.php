@extends('layouts.app')

@section('content')
<h1>Business settings</h1>

@if ($maintenance)
    <div style="background:#fff4e5;border:1px solid #f0b429;padding:12px;border-radius:8px;margin-bottom:16px">
        This company is currently in maintenance mode, set by the platform administrator.
    </div>
@endif

@if (session('status'))
    <div style="background:#dcfae6;color:#1b7a3d;padding:12px;border-radius:8px;margin-bottom:16px">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div style="background:#fee4e2;color:#b42318;padding:12px;border-radius:8px;margin-bottom:16px">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<style>
    .tabs { display:flex; gap:4px; flex-wrap:wrap; border-bottom:2px solid #e4e7ec; margin-bottom:20px; }
    .tab { padding:10px 16px; cursor:pointer; border:0; background:none; color:#475467; font-weight:600; border-bottom:3px solid transparent; margin-bottom:-2px; }
    .tab.active { color:#1b7a3d; border-bottom-color:#1b7a3d; }
    .pane { display:none; } .pane.active { display:block; }
    .pane label { display:block; margin-bottom:14px; }
    .pane input:not([type=checkbox]) { display:block; width:100%; max-width:460px; padding:8px; }
    .badge { display:inline-block; padding:2px 10px; border-radius:99px; font-size:12px; }
    .ok { background:#dcfae6; color:#1b7a3d; } .no { background:#f2f4f7; color:#667085; }
</style>

@php $v = fn ($k, $d = '') => old($k, $values[$k] ?? $d); @endphp

<div class="tabs">
    <button type="button" class="tab active" data-pane="business">Business</button>
    <button type="button" class="tab" data-pane="currency">Currency &amp; tax</button>
    <button type="button" class="tab" data-pane="payments">Payments</button>
    <button type="button" class="tab" data-pane="sms">SMS</button>
</div>

<form method="POST" action="{{ route('settings.update') }}" class="card">
    @csrf
    @method('PUT')

    <div class="pane active" id="pane-business">
        <label>Business name <input name="business_name" value="{{ $v('business_name') }}" required></label>
        <label>Phone <input name="business_phone" value="{{ $v('business_phone') }}"></label>
        <label>Address <input name="business_address" value="{{ $v('business_address') }}"></label>
        <label>Receipt footer <input name="receipt_footer" value="{{ $v('receipt_footer') }}" placeholder="Thank you for shopping with us"></label>
    </div>

    <div class="pane" id="pane-currency">
        <label>Currency (3 letters, e.g. KES, UGX, USD)
            <input name="currency" maxlength="3" value="{{ $v('currency', \App\Models\PlatformSetting::get('default_currency', 'KES')) }}" required>
        </label>
        <label>Timezone
            <input name="timezone" value="{{ $v('timezone', \App\Models\PlatformSetting::get('default_timezone', 'Africa/Nairobi')) }}" required>
        </label>
        <label>Tax rate (%)
            <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $v('tax_rate', '0') }}" required>
        </label>
        <p>Preview: <strong>@money(1234.5)</strong></p>
    </div>

    <div class="pane" id="pane-payments">
        <label><input type="checkbox" name="cash_enabled" value="1" @checked($v('cash_enabled', '1') == '1')> Accept cash</label>
        <label><input type="checkbox" name="mpesa_enabled" value="1" @checked($v('mpesa_enabled', '0') == '1')> Accept M-Pesa (PayHero)
            <span class="badge {{ $mpesaReady ? 'ok' : 'no' }}">{{ $mpesaReady ? 'Credentials saved' : 'Credentials missing' }}</span>
        </label>
        <label>PayHero API username <input name="payhero_username" value="{{ $v('payhero_username') }}" autocomplete="off"></label>
        <label>PayHero API password
            <input type="password" name="payhero_password" autocomplete="new-password"
                   placeholder="{{ ($hasSecret['payhero_password'] ?? false) ? '•••••• saved (leave blank to keep)' : 'Not set' }}">
        </label>
        <label>PayHero channel ID <input name="payhero_channel_id" value="{{ $v('payhero_channel_id') }}"></label>
        <small>These credentials belong to this company only and are stored encrypted. Payments go to your own account.</small>
    </div>

    <div class="pane" id="pane-sms">
        <label><input type="checkbox" name="sms_enabled" value="1" @checked($v('sms_enabled', '0') == '1')> Send SMS receipts and alerts</label>
        <label>Sender ID <input name="sms_sender_id" maxlength="20" value="{{ $v('sms_sender_id') }}"></label>
        <label>Own SMS API key (optional; the platform's SMS account is used if blank)
            <input type="password" name="sms_api_key" autocomplete="new-password"
                   placeholder="{{ ($hasSecret['sms_api_key'] ?? false) ? '•••••• saved (leave blank to keep)' : 'Not set' }}">
        </label>
    </div>

    <button type="submit">Save settings</button>
</form>

<script>
    const tabs = document.querySelectorAll('.tab');
    function show(name) {
        if (!document.getElementById('pane-' + name)) name = 'business';
        tabs.forEach(t => t.classList.toggle('active', t.dataset.pane === name));
        document.querySelectorAll('.pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + name));
        history.replaceState(null, '', '#' + name);
    }
    tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.pane)));
    show((location.hash || '#business').slice(1));
</script>
@endsection