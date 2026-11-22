@php $v = fn ($k, $d = '') => old($k, $values[$k] ?? $d); @endphp

<h2>Business</h2>
<label>Business name <input name="business_name" value="{{ $v('business_name') }}" required></label>
<label>Phone <input name="business_phone" value="{{ $v('business_phone') }}"></label>
<label>Address <input name="business_address" value="{{ $v('business_address') }}"></label>
<label>Receipt footer <input name="receipt_footer" value="{{ $v('receipt_footer') }}" placeholder="Thank you for shopping with us"></label>

<h2>Currency and tax</h2>
<label>Currency (3 letters, e.g. KES, USD, UGX)
    <input name="currency" maxlength="3" value="{{ $v('currency', \App\Models\PlatformSetting::get('default_currency', 'KES')) }}" required>
</label>
<label>Timezone
    <input name="timezone" value="{{ $v('timezone', \App\Models\PlatformSetting::get('default_timezone', 'Africa/Nairobi')) }}" required>
</label>
<label>Tax rate (%)
    <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $v('tax_rate', '0') }}" required>
</label>

<h2>Payments</h2>
<label><input type="checkbox" name="cash_enabled" value="1" @checked($v('cash_enabled', '1') == '1')> Accept cash</label>
<label><input type="checkbox" name="mpesa_enabled" value="1" @checked($v('mpesa_enabled', '0') == '1')> Accept M-Pesa (PayHero)</label>
<label>PayHero API username <input name="payhero_username" value="{{ $v('payhero_username') }}" autocomplete="off"></label>
<label>PayHero API password
    <input type="password" name="payhero_password" autocomplete="new-password"
           placeholder="{{ ($hasSecret['payhero_password'] ?? false) ? '•••••• saved (leave blank to keep)' : 'Not set' }}">
</label>
<label>PayHero channel ID <input name="payhero_channel_id" value="{{ $v('payhero_channel_id') }}"></label>
<small>These credentials belong to this company only and are stored encrypted.</small>

<h2>SMS</h2>
<label><input type="checkbox" name="sms_enabled" value="1" @checked($v('sms_enabled', '0') == '1')> Send SMS receipts and alerts</label>
<label>Sender ID <input name="sms_sender_id" maxlength="20" value="{{ $v('sms_sender_id') }}"></label>
<label>Own SMS API key (optional, otherwise the platform's SMS account is used)
    <input type="password" name="sms_api_key" autocomplete="new-password"
           placeholder="{{ ($hasSecret['sms_api_key'] ?? false) ? '•••••• saved (leave blank to keep)' : 'Not set' }}">
</label>