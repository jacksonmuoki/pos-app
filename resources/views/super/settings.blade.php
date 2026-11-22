
@extends('super.layout')

@section('title', 'Platform Settings')
@section('breadcrumb', 'Settings')

@section('content')

<style>
    .settings-page {
        color: #1e293b;
    }

    .settings-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
        gap: 15px;
    }

    .settings-heading h1 {
        margin: 0;
        font-size: 23px;
        font-weight: 800;
        color: #1e293b;
        font-family: 'Manrope', sans-serif;
    }

    .settings-heading p {
        margin: 6px 0 0;
        color: #7b8a9c;
        font-size: 12px;
    }

    .settings-card {
        background: #fff;
        border: 1px solid #e6eaef;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.03);
        overflow: hidden;
    }

    .settings-tabs {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        padding: 12px 18px 0;
        background: #fafbfd;
        border-bottom: 1px solid #e6eaef;
    }

    .settings-tab {
        padding: 13px 15px;
        cursor: pointer;
        border: 0;
        background: transparent;
        color: #7b8a9c;
        font-size: 12px;
        font-weight: 700;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
        transition: .2s ease;
        white-space: nowrap;
    }

    .settings-tab:hover {
        color: #1e293b;
    }

    .settings-tab.active {
        color: #128d51;
        border-bottom-color: #128d51;
    }

    .settings-pane {
        display: none;
        padding: 25px;
    }

    .settings-pane.active {
        display: block;
    }

    .settings-section-title {
        margin-bottom: 22px;
    }

    .settings-section-title h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        color: #1e293b;
    }

    .settings-section-title p {
        margin: 6px 0 0;
        font-size: 12px;
        color: #7b8a9c;
    }

    .settings-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .settings-field {
        display: block;
        min-width: 0;
    }

    .settings-field.full {
        grid-column: 1 / -1;
    }

    .settings-field label,
    .settings-field > span {
        display: block;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }

    .settings-field input:not([type="checkbox"]),
    .settings-field textarea,
    .settings-field select {
        display: block;
        width: 100%;
        height: 42px;
        padding: 0 12px;
        border: 1px solid #e1e6ed;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        outline: none;
        font-size: 12px;
        transition: .2s ease;
        box-sizing: border-box;
    }

    .settings-field textarea {
        height: auto;
        min-height: 100px;
        padding: 12px;
        resize: vertical;
        line-height: 1.6;
    }

    .settings-field input:focus,
    .settings-field textarea:focus,
    .settings-field select:focus {
        border-color: #128d51;
        box-shadow: 0 0 0 3px rgba(18,141,81,.08);
    }

    .settings-help {
        display: block;
        margin-top: 7px;
        color: #94a3b8;
        font-size: 11px;
        line-height: 1.6;
    }

    .settings-notice {
        padding: 13px 15px;
        border-radius: 8px;
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        color: #166534;
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .settings-warning {
        padding: 14px 15px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .settings-toggle {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 15px;
        background: #fafbfd;
        border: 1px solid #e6eaef;
        border-radius: 9px;
        margin-bottom: 15px;
    }

    .settings-toggle input {
        width: 17px;
        height: 17px;
        margin-top: 1px;
        accent-color: #128d51;
        cursor: pointer;
    }

    .settings-toggle label {
        cursor: pointer;
        margin: 0;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.6;
    }

    .settings-toggle small {
        display: block;
        margin-top: 4px;
        color: #7b8a9c;
        font-weight: 400;
    }

    .settings-footer {
        display: flex;
        justify-content: flex-end;
        padding: 18px 25px;
        border-top: 1px solid #e6eaef;
        background: #fafbfd;
    }

    .settings-save {
        height: 40px;
        padding: 0 20px;
        background: #128d51;
        color: #fff;
        border: 0;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: .2s ease;
    }

    .settings-save:hover {
        background: #0e7542;
    }

    .settings-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .settings-table {
        width: 100%;
        min-width: 850px;
        border-collapse: collapse;
    }

    .settings-table th {
        background: #fafbfd;
        color: #7b8a9c;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .6px;
        font-weight: 800;
        padding: 15px 18px;
        text-align: left;
        white-space: nowrap;
    }

    .settings-table td {
        padding: 15px 18px;
        border-top: 1px solid #f0f3f7;
        font-size: 12px;
        color: #334155;
        vertical-align: middle;
    }

    .settings-table tbody tr:hover {
        background: #fafbfd;
    }

    .settings-company {
        font-weight: 800;
        color: #1e293b;
    }

    .settings-muted {
        color: #94a3b8;
    }

    .settings-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .settings-pill.on {
        background: #dcfce7;
        color: #15803d;
    }

    .settings-pill.off {
        background: #f1f5f9;
        color: #64748b;
    }

    .settings-pill.warn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .settings-edit {
        color: #128d51;
        font-weight: 800;
        text-decoration: none;
    }

    .settings-edit:hover {
        text-decoration: underline;
    }

    .settings-empty {
        padding: 45px 20px !important;
        text-align: center;
        color: #94a3b8 !important;
    }

    .settings-empty strong {
        display: block;
        color: #334155;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .settings-integration-link {
        color: #128d51;
        font-weight: 800;
        text-decoration: none;
    }

    .settings-integration-link:hover {
        text-decoration: underline;
    }

    .settings-company-heading {
        padding: 20px;
        border-bottom: 1px solid #e6eaef;
    }

    .settings-company-heading h2 {
        font-size: 15px;
        font-weight: 800;
        margin: 0;
    }

    .settings-company-heading p {
        color: #7b8a9c;
        font-size: 12px;
        margin: 5px 0 0;
    }

    @media(max-width: 850px) {
        .settings-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
        }

        .settings-fields {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width: 520px) {
        .settings-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .settings-pane {
            padding: 18px;
        }

        .settings-footer {
            padding: 15px 18px;
        }

        .settings-save {
            width: 100%;
        }

        .settings-tab {
            padding: 12px 11px;
        }
    }
</style>

<div class="settings-page">

    <div class="settings-heading">
        <div>
            <h1>Platform Settings</h1>
            <p>Configure platform preferences, company access and operational settings.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="settings-notice">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="settings-warning">
            <strong>Please correct the following errors:</strong>
            <ul style="margin:8px 0 0;padding-left:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="settings-card">

        <div class="settings-tabs" id="settings-tabs">

            <button type="button" class="settings-tab active" data-pane="general">
                General
            </button>

            <button type="button" class="settings-tab" data-pane="currency">
                Currency &amp; locale
            </button>

            <button type="button" class="settings-tab" data-pane="signup">
                Signup &amp; trial
            </button>

            <button type="button" class="settings-tab" data-pane="maintenance">
                Maintenance
            </button>

            <button type="button" class="settings-tab" data-pane="messaging">
                SMS &amp; payments
            </button>

            <button type="button" class="settings-tab" data-pane="companies">
                Companies
            </button>

        </div>

        <form method="POST" action="{{ route('super.settings.update') }}" id="settings-form">

            @csrf
            @method('PUT')

            {{-- GENERAL SETTINGS --}}
            <div class="settings-pane active" id="pane-general">

                <div class="settings-section-title">
                    <h2>General platform information</h2>
                    <p>Manage the platform identity and official support contacts.</p>
                </div>

                <div class="settings-fields">

                    <div class="settings-field">
                        <label for="platform_name">Platform name</label>
                        <input
                            type="text"
                            id="platform_name"
                            name="platform_name"
                            value="{{ old('platform_name', $settings['platform_name']) }}"
                            required
                        >
                    </div>

                    <div class="settings-field">
                        <label for="support_email">Support email</label>
                        <input
                            type="email"
                            id="support_email"
                            name="support_email"
                            value="{{ old('support_email', $settings['support_email']) }}"
                            required
                        >
                    </div>

                    <div class="settings-field">
                        <label for="support_phone">Support phone</label>
                        <input
                            type="text"
                            id="support_phone"
                            name="support_phone"
                            value="{{ old('support_phone', $settings['support_phone']) }}"
                        >
                    </div>

                </div>
            </div>

            {{-- CURRENCY SETTINGS --}}
            <div class="settings-pane" id="pane-currency">

                <div class="settings-section-title">
                    <h2>Currency and regional preferences</h2>
                    <p>Configure the default localization settings for newly registered companies.</p>
                </div>

                <div class="settings-notice">
                    These settings act as platform defaults. Each company can configure its own currency and timezone.
                </div>

                <div class="settings-fields">

                    <div class="settings-field">
                        <label for="default_currency">Default currency</label>
                        <input
                            type="text"
                            id="default_currency"
                            name="default_currency"
                            maxlength="3"
                            value="{{ old('default_currency', $settings['default_currency']) }}"
                            required
                        >
                        <small class="settings-help">Three-letter currency code, for example KES.</small>
                    </div>

                    <div class="settings-field">
                        <label for="default_timezone">Default timezone</label>
                        <input
                            type="text"
                            id="default_timezone"
                            name="default_timezone"
                            value="{{ old('default_timezone', $settings['default_timezone']) }}"
                            required
                        >
                        <small class="settings-help">Example: Africa/Nairobi.</small>
                    </div>

                    <div class="settings-field">
                        <label for="date_format">Date format (PHP)</label>
                        <input
                            type="text"
                            id="date_format"
                            name="date_format"
                            value="{{ old('date_format', $settings['date_format']) }}"
                            required
                        >
                        <small class="settings-help">Example: d M Y.</small>
                    </div>

                </div>
            </div>

            {{-- SIGNUP SETTINGS --}}
            <div class="settings-pane" id="pane-signup">

                <div class="settings-section-title">
                    <h2>Signup and trial configuration</h2>
                    <p>Control company registration and trial subscription duration.</p>
                </div>

                <div class="settings-fields">

                    <div class="settings-field">
                        <label for="trial_days">Trial length (days)</label>
                        <input
                            type="number"
                            id="trial_days"
                            name="trial_days"
                            min="0"
                            max="365"
                            value="{{ old('trial_days', $settings['trial_days']) }}"
                            required
                        >
                        <small class="settings-help">Set between 0 and 365 days.</small>
                    </div>

                    <div class="settings-field full">

                        <div class="settings-toggle">
                            <input
                                type="checkbox"
                                id="signup_enabled"
                                name="signup_enabled"
                                value="1"
                                @checked((string) old('signup_enabled', $settings['signup_enabled']) === '1')
                            >

                            <label for="signup_enabled">
                                Allow company signup
                                <small>Enable or disable new company registrations on the platform.</small>
                            </label>
                        </div>

                    </div>

                </div>
            </div>

            {{-- MAINTENANCE SETTINGS --}}
            <div class="settings-pane" id="pane-maintenance">

                <div class="settings-section-title">
                    <h2>Platform maintenance</h2>
                    <p>Manage platform availability and maintenance access permissions.</p>
                </div>

                <div class="settings-warning">
                    Enabling platform-wide maintenance blocks access for all companies. The Super Admin area remains available.
                </div>

                <div class="settings-fields">

                    <div class="settings-field full">

                        <div class="settings-toggle">
                            <input
                                type="checkbox"
                                id="maintenance_mode"
                                name="maintenance_mode"
                                value="1"
                                @checked((string) old('maintenance_mode', $settings['maintenance_mode']) === '1')
                            >

                            <label for="maintenance_mode">
                                Enable platform-wide maintenance
                                <small>Restrict company access while maintenance is in progress.</small>
                            </label>
                        </div>

                    </div>

                    <div class="settings-field full">
                        <label for="maintenance_message">Maintenance message</label>
                        <textarea
                            id="maintenance_message"
                            name="maintenance_message"
                            rows="4"
                            maxlength="500"
                            required
                        >{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                    </div>

                    <div class="settings-field full">
                        <label for="maintenance_ips">Allowed IP addresses</label>
                        <input
                            type="text"
                            id="maintenance_ips"
                            name="maintenance_ips"
                            value="{{ old('maintenance_ips', $settings['maintenance_ips']) }}"
                            placeholder="41.90.1.10, 105.160.2.5"
                        >
                        <small class="settings-help">
                            Separate multiple IP addresses using commas.
                        </small>
                    </div>

                    <div class="settings-field full">
                        <small class="settings-help">
                            Payment callbacks are never blocked. To pause a single company, use its settings in the Companies tab.
                        </small>
                    </div>

                </div>
            </div>

            {{-- MESSAGING SETTINGS --}}
            <div class="settings-pane" id="pane-messaging">

                <div class="settings-section-title">
                    <h2>SMS and payment integrations</h2>
                    <p>Manage the external services connected to your platform.</p>
                </div>

                <div class="settings-notice">
                    Platform SMS and payment defaults are managed separately in the Integrations section.
                </div>

                <div class="settings-fields">

                    <div class="settings-field full">
                        <label>Platform integrations</label>
                        <p style="font-size:12px;color:#64748b;line-height:1.8">
                            Configure platform-level SMS and payment integrations from the
                            <a href="{{ route('super.integrations') }}" class="settings-integration-link">
                                Integrations page
                            </a>.
                        </p>
                    </div>

                    <div class="settings-field full">
                        <label>Company-specific credentials</label>
                        <p style="font-size:12px;color:#64748b;line-height:1.8">
                            Each company maintains its own PayHero credentials and may use its own SMS key.
                            Open a company in the Companies tab to edit its integration settings.
                        </p>
                    </div>

                </div>
            </div>

            {{-- COMPANIES PANE --}}
            <div class="settings-pane" id="pane-companies">

                <div class="settings-section-title">
                    <h2>Company management</h2>
                    <p>Monitor company configurations, payment integrations and maintenance status.</p>
                </div>

                <div class="settings-table-wrap">

                    <table class="settings-table">

                        <thead>
                            <tr>
                                <th>Company ID</th>
                                <th>Business name</th>
                                <th>Currency</th>
                                <th>M-Pesa</th>
                                <th>SMS</th>
                                <th>Maintenance</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($companies as $c)

                                <tr>

                                    <td>
                                        <span class="settings-muted">#{{ $c['id'] }}</span>
                                    </td>

                                    <td>
                                        <span class="settings-company">{{ $c['name'] }}</span>
                                    </td>

                                    <td>
                                        {{ $c['currency'] }}
                                    </td>

                                    <td>
                                        <span class="settings-pill {{ $c['payhero'] ? 'on' : 'off' }}">
                                            {{ $c['payhero'] ? 'Configured' : 'Not set' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="settings-pill {{ $c['sms'] ? 'on' : 'off' }}">
                                            {{ $c['sms'] ? 'On' : 'Off' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="settings-pill {{ $c['maintenance'] ? 'warn' : 'on' }}">
                                            {{ $c['maintenance'] ? 'In maintenance' : 'Live' }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($c['error'])
                                            <span class="settings-pill warn">DB unreachable</span>
                                        @else
                                            <a
                                                href="{{ route('super.company-settings.edit', $c['id']) }}"
                                                class="settings-edit"
                                            >
                                                Edit settings
                                            </a>
                                        @endif
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="settings-empty">
                                        <strong>No companies found</strong>
                                        Registered companies will appear here.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            {{-- SAVE BUTTON --}}
            <div class="settings-footer" id="settings-footer">
                <button type="submit" class="settings-save" id="save-btn">
                    Save settings
                </button>
            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const tabs = document.querySelectorAll('.settings-tab');
    const panes = document.querySelectorAll('.settings-pane');
    const saveFooter = document.getElementById('settings-footer');
    const form = document.getElementById('settings-form');

    function show(name, updateHash = true) {

        const selectedTab = document.querySelector(
            '.settings-tab[data-pane="' + name + '"]'
        );

        if (!selectedTab) {
            name = 'general';
        }

        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.pane === name);
        });

        panes.forEach(pane => {
            pane.classList.toggle('active', pane.id === 'pane-' + name);
        });

        if (saveFooter) {
            saveFooter.style.display = name === 'companies' ? 'none' : '';
        }

        if (updateHash) {
            history.replaceState(null, '', '#' + name);
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            show(this.dataset.pane);
        });
    });

    const initialTab = window.location.hash.replace('#', '');

    show(initialTab || 'general', false);

    window.addEventListener('hashchange', function () {
        show(window.location.hash.replace('#', '') || 'general', false);
    });

    if (form) {
        form.addEventListener('submit', function () {
            const button = document.getElementById('save-btn');

            if (button) {
                button.disabled = true;
                button.textContent = 'Saving settings...';
            }
        });
    }

});
</script>

@endsection
