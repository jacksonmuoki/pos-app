
@php
    $v = fn ($k, $d = '') => old($k, $values[$k] ?? $d);
@endphp

<style>
    .business-settings-fields {
        --bs-primary: #128d51;
        --bs-primary-dark: #0d7040;
        --bs-text: #1e293b;
        --bs-muted: #7b8a9c;
        --bs-border: #e6eaef;
        --bs-bg: #f8fafc;
        color: var(--bs-text);
        font-family: 'Manrope', sans-serif;
    }

    .business-settings-fields,
    .business-settings-fields * {
        box-sizing: border-box;
    }

    .business-settings-fields .business-section {
        background: #fff;
        border: 1px solid var(--bs-border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    .business-settings-fields .business-section h2 {
        color: var(--bs-text);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -.3px;
        margin: 0 0 22px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--bs-border);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .business-settings-fields .business-section h2::before {
        content: "";
        display: block;
        width: 4px;
        height: 19px;
        border-radius: 5px;
        background: var(--bs-primary);
    }

    .business-settings-fields .business-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .business-settings-fields .business-field {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .business-settings-fields .business-field label {
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        margin: 0;
    }

    .business-settings-fields .business-field input {
        width: 100%;
        min-height: 43px;
        padding: 10px 13px;
        border: 1px solid var(--bs-border);
        border-radius: 8px;
        outline: none;
        background: #fff;
        color: var(--bs-text);
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        transition: border-color .2s, box-shadow .2s;
    }

    .business-settings-fields .business-field input::placeholder {
        color: #a0acb9;
        font-weight: 400;
    }

    .business-settings-fields .business-field input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .09);
    }

    .business-settings-fields .business-field small,
    .business-settings-fields .business-help {
        color: var(--bs-muted);
        font-size: 11px;
        line-height: 1.6;
    }

    .business-settings-fields .business-toggle-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 22px;
    }

    .business-settings-fields .business-toggle {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 13px 15px;
        border: 1px solid var(--bs-border);
        border-radius: 9px;
        background: #fff;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background .2s, border-color .2s;
    }

    .business-settings-fields .business-toggle:hover {
        border-color: rgba(18, 141, 81, .4);
        background: #f8fcf9;
    }

    .business-settings-fields .business-toggle input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: var(--bs-primary);
        cursor: pointer;
        flex-shrink: 0;
    }

    .business-settings-fields .business-help {
        display: block;
        margin-top: 15px;
        padding: 11px 13px;
        background: #f8fafc;
        border: 1px solid var(--bs-border);
        border-radius: 8px;
    }

    @media (max-width: 650px) {
        .business-settings-fields .business-section {
            padding: 17px;
            margin-bottom: 15px;
        }

        .business-settings-fields .business-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>

<div class="business-settings-fields">

    {{-- Business information --}}
    <section class="business-section">
        <h2>Business Information</h2>

        <div class="business-grid">
            <div class="business-field">
                <label for="business_name">Business Name</label>
                <input type="text" id="business_name" name="business_name"
                       value="{{ $v('business_name') }}" placeholder="Enter business name" required>
            </div>

            <div class="business-field">
                <label for="business_phone">Business Phone</label>
                <input type="text" id="business_phone" name="business_phone"
                       value="{{ $v('business_phone') }}" placeholder="Business phone number">
            </div>

            <div class="business-field">
                <label for="business_address">Business Address</label>
                <input type="text" id="business_address" name="business_address"
                       value="{{ $v('business_address') }}" placeholder="Enter business address">
            </div>

            <div class="business-field">
                <label for="receipt_footer">Receipt Footer</label>
                <input type="text" id="receipt_footer" name="receipt_footer"
                       value="{{ $v('receipt_footer') }}"
                       placeholder="Thank you for shopping with us">
            </div>
        </div>
    </section>

    {{-- Currency and tax --}}
    <section class="business-section">
        <h2>Currency &amp; Tax Configuration</h2>

        <div class="business-grid">
            <div class="business-field">
                <label for="currency">Currency Code</label>
                <input type="text" id="currency" name="currency"
                       maxlength="3" minlength="3"
                       value="{{ $v('currency', \App\Models\PlatformSetting::get('default_currency', 'KES')) }}"
                       placeholder="KES" required>
                <small>Three-letter code, e.g. KES, USD or UGX.</small>
            </div>

            <div class="business-field">
                <label for="timezone">Timezone</label>
                <input type="text" id="timezone" name="timezone"
                       value="{{ $v('timezone', \App\Models\PlatformSetting::get('default_timezone', 'Africa/Nairobi')) }}"
                       placeholder="Africa/Nairobi" required>
            </div>

            <div class="business-field">
                <label for="tax_rate">Tax Rate (%)</label>
                <input type="number" id="tax_rate" step="0.01"
                       min="0" max="100" name="tax_rate"
                       value="{{ $v('tax_rate', '0') }}" required>
            </div>
        </div>
    </section>

    {{-- Payments --}}
    <section class="business-section">
        <h2>Payment Methods</h2>

        <div class="business-toggle-list">
            <label class="business-toggle">
                <input type="checkbox" name="cash_enabled" value="1"
                       @checked((string) $v('cash_enabled', '1') === '1')>
                <span>Accept Cash Payments</span>
            </label>

            <label class="business-toggle">
                <input type="checkbox" name="mpesa_enabled" value="1"
                       @checked((string) $v('mpesa_enabled', '0') === '1')>
                <span>Accept M-Pesa Payments (PayHero)</span>
            </label>
        </div>

        <div class="business-grid">
            <div class="business-field">
                <label for="payhero_username">PayHero API Username</label>
                <input type="text" id="payhero_username" name="payhero_username"
                       value="{{ $v('payhero_username') }}" autocomplete="off"
                       placeholder="Enter API username">
            </div>

            <div class="business-field">
                <label for="payhero_password">PayHero API Password</label>
                <input type="password" id="payhero_password" name="payhero_password"
                       autocomplete="new-password"
                       placeholder="{{ ($hasSecret['payhero_password'] ?? false) ? '•••••• saved (leave blank to keep)' : 'Not set' }}">
            </div>

            <div class="business-field">
                <label for="payhero_channel_id">PayHero Channel ID</label>
                <input type="text" id="payhero_channel_id" name="payhero_channel_id"
                       value="{{ $v('payhero_channel_id') }}" placeholder="Enter channel ID">
            </div>
        </div>

        <small class="business-help">
            Payment credentials belong to this company and are stored encrypted.
            Leave the password blank to retain the existing credentials.
        </small>
    </section>

    {{-- SMS --}}
    <section class="business-section">
        <h2>SMS Notifications</h2>

        <div class="business-toggle-list">
            <label class="business-toggle">
                <input type="checkbox" name="sms_enabled" value="1"
                       @checked((string) $v('sms_enabled', '0') === '1')>
                <span>Send SMS Receipts &amp; Alerts</span>
            </label>
        </div>

        <div class="business-grid">
            <div class="business-field">
                <label for="sms_sender_id">SMS Sender ID</label>
                <input type="text" id="sms_sender_id" name="sms_sender_id"
                       maxlength="20" value="{{ $v('sms_sender_id') }}"
                       placeholder="Enter sender ID">
            </div>

            <div class="business-field">
                <label for="sms_api_key">SMS API Key</label>
                <input type="password" id="sms_api_key" name="sms_api_key"
                       autocomplete="new-password"
                       placeholder="{{ ($hasSecret['sms_api_key'] ?? false) ? '•••••• saved (leave blank to keep)' : 'Not set' }}">
            </div>
        </div>

        <small class="business-help">
            Optional company SMS API key. If not provided, the platform's SMS account will be used.
        </small>
    </section>

</div>
