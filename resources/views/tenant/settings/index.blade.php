
@extends('layouts.app')

@section('title', 'Company Settings')
@section('page-heading', 'Company Settings')

@php
    $setting = fn ($key, $default = '') => old($key, $settings[$key] ?? $default);

    $boolSetting = fn ($key, $default = false) =>
        filter_var($setting($key, $default), FILTER_VALIDATE_BOOLEAN);

    $isOwner = $isOwner ?? false;
    $permissionGroups = $permissionGroups ?? [];
    $roles = $roles ?? collect();
    $staff = $staff ?? collect();

    $sections = [
        'company' => 'Company profile',
        'general' => 'Regional preferences',
        'receipt' => 'Receipt configuration',
        'payments' => 'Payment methods',
        'tax' => 'Tax configuration',
        'inventory' => 'Inventory control',
        'roles' => 'Roles & permissions',
        'staff' => 'Staff access',
        'security' => 'Security policies',
    ];

    $allSettings = [
        'company_name', 'company_email', 'company_phone', 'company_address',
        'currency', 'timezone', 'date_format',
        'receipt_size', 'receipt_footer', 'receipt_auto_print',
        'allow_cash', 'allow_mpesa', 'allow_card',
        'tax_enabled', 'tax_name', 'tax_rate',
        'allow_negative_stock', 'low_stock_threshold',
        'allow_discounts', 'require_customer', 'require_manager_void',
        'password_min_length', 'session_timeout',
    ];

    $hiddenSettings = function (array $exclude = []) use ($allSettings, $setting) {
        foreach ($allSettings as $key) {
            if (in_array($key, $exclude, true)) {
                continue;
            }

            echo '<input type="hidden" name="' . e($key) . '" value="' . e($setting($key, '')) . '">';
        }
    };

    $isChecked = fn ($key, $default = false) => $boolSetting($key, $default);

    $panelFields = [
        'company' => ['company_name', 'company_email', 'company_phone', 'company_address'],
        'general' => ['currency', 'timezone', 'date_format'],
        'receipt' => ['receipt_size', 'receipt_footer', 'receipt_auto_print'],
        'payments' => ['allow_cash', 'allow_mpesa', 'allow_card'],
        'tax' => ['tax_enabled', 'tax_name', 'tax_rate'],
        'inventory' => ['allow_negative_stock', 'low_stock_threshold'],
        'roles' => ['name', 'permissions'],
        'security' => ['password_min_length', 'session_timeout'],
    ];

    $errorPanel = 'company';

    foreach ($panelFields as $panel => $fields) {
        if (count(array_intersect(array_keys($errors->getMessages()), $fields))) {
            $errorPanel = $panel;
            break;
        }
    }
@endphp

@push('styles')
<style>
.settings-page {
    --sp-bg: #f5f7f5;
    --sp-card: #fff;
    --sp-soft: #f8faf8;
    --sp-text: #18251e;
    --sp-muted: #78847c;
    --sp-border: #e3e9e4;
    --sp-input: #fff;
    --sp-primary: #176b3a;
    --sp-primary-hover: #12572e;
    --sp-primary-soft: #edf5ef;
    --sp-danger: #c43c3c;
    --sp-success: #237747;
    --sp-radius: 12px;

    width: 100%;
    max-width: 100%;
    min-width: 0;
    color: var(--sp-text);
    font-size: 13px;
    overflow-x: clip;
}

html[data-theme="dark"] .settings-page,
html.dark .settings-page,
body.dark .settings-page,
[data-bs-theme="dark"] .settings-page {
    --sp-bg: #111a14;
    --sp-card: #19251d;
    --sp-soft: #202d24;
    --sp-text: #e7eee8;
    --sp-muted: #9aa99e;
    --sp-border: #304137;
    --sp-input: #131d16;
    --sp-primary: #58a978;
    --sp-primary-hover: #70ba8d;
    --sp-primary-soft: #203a29;
    --sp-danger: #f07878;
    --sp-success: #77c393;
}

.settings-page,
.settings-page * {
    box-sizing: border-box;
}

.settings-page button,
.settings-page input,
.settings-page select,
.settings-page textarea {
    font: inherit;
}

.settings-page button {
    cursor: pointer;
}

.settings-page .sp-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.settings-page .sp-eyebrow {
    color: var(--sp-primary);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 7px;
}

.settings-page .sp-heading h1 {
    font-size: clamp(23px, 3vw, 30px);
    line-height: 1.2;
    font-weight: 800;
    letter-spacing: -.8px;
    margin: 0 0 7px;
    color: var(--sp-text);
}

.settings-page .sp-heading p {
    color: var(--sp-muted);
    margin: 0;
    font-size: 12px;
    line-height: 1.7;
}

.settings-page .sp-header-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--sp-border);
    border-radius: 8px;
    background: var(--sp-card);
    padding: 9px 12px;
    color: var(--sp-muted);
    font-size: 11px;
    font-weight: 700;
}

.settings-page .sp-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--sp-success);
}

.settings-page .sp-layout {
    display: grid;
    grid-template-columns: 235px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
    min-width: 0;
}

.settings-page .sp-sidebar {
    position: sticky;
    top: 85px;
    background: var(--sp-card);
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius);
    padding: 9px;
    min-width: 0;
}

.settings-page .sp-nav-heading {
    padding: 12px 11px 10px;
    color: var(--sp-muted);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.1px;
    text-transform: uppercase;
}

.settings-page .sp-nav {
    display: grid;
    gap: 3px;
}

.settings-page .sp-nav button {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    min-width: 0;
    min-height: 42px;
    padding: 10px 11px;
    text-align: left;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--sp-muted);
    font-size: 11px;
    font-weight: 650;
    transition: .18s ease;
}

.settings-page .sp-nav button:hover {
    color: var(--sp-text);
    background: var(--sp-soft);
}

.settings-page .sp-nav button.active {
    color: var(--sp-primary);
    background: var(--sp-primary-soft);
    font-weight: 800;
}

.settings-page .sp-nav-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: transparent;
    flex-shrink: 0;
}

.settings-page .sp-nav button.active .sp-nav-indicator {
    background: var(--sp-primary);
}

.settings-page .sp-main {
    min-width: 0;
}

.settings-page .sp-panel {
    display: none;
    min-width: 0;
}

.settings-page .sp-panel.active {
    display: block;
    animation: spEnter .2s ease;
}

@keyframes spEnter {
    from { opacity: .5; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-page .sp-section-heading {
    margin: 0 0 16px;
}

.settings-page .sp-section-heading h2 {
    color: var(--sp-text);
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -.3px;
    margin: 0 0 5px;
}

.settings-page .sp-section-heading p {
    color: var(--sp-muted);
    font-size: 11px;
    line-height: 1.6;
    margin: 0;
}

.settings-page .sp-card {
    min-width: 0;
    background: var(--sp-card);
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius);
    padding: 22px;
    margin-bottom: 16px;
}

.settings-page .sp-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    border-bottom: 1px solid var(--sp-border);
    padding-bottom: 16px;
    margin-bottom: 19px;
}

.settings-page .sp-card-top h3 {
    color: var(--sp-text);
    font-size: 13px;
    font-weight: 800;
    margin: 0 0 5px;
}

.settings-page .sp-card-top p {
    color: var(--sp-muted);
    font-size: 11px;
    line-height: 1.6;
    margin: 0;
}

.settings-page .sp-card-label {
    color: var(--sp-primary);
    background: var(--sp-primary-soft);
    padding: 6px 9px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .4px;
    text-transform: uppercase;
    white-space: nowrap;
}

.settings-page .sp-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.settings-page .sp-field {
    min-width: 0;
}

.settings-page .sp-field.full {
    grid-column: 1 / -1;
}

.settings-page .sp-field label {
    display: block;
    color: var(--sp-text);
    font-size: 11px;
    font-weight: 750;
    margin-bottom: 7px;
}

.settings-page .sp-field input,
.settings-page .sp-field select,
.settings-page .sp-field textarea {
    width: 100%;
    min-width: 0;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid var(--sp-border);
    border-radius: 7px;
    background: var(--sp-input);
    color: var(--sp-text);
    outline: none;
    font-size: 12px;
    transition: border-color .18s, box-shadow .18s;
}

.settings-page .sp-field textarea {
    min-height: 88px;
    resize: vertical;
}

.settings-page .sp-field input:focus,
.settings-page .sp-field select:focus,
.settings-page .sp-field textarea:focus {
    border-color: var(--sp-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--sp-primary) 13%, transparent);
}

.settings-page .sp-field input::placeholder,
.settings-page .sp-field textarea::placeholder {
    color: var(--sp-muted);
    opacity: .8;
}

.settings-page .sp-hint {
    margin-top: 6px;
    color: var(--sp-muted);
    font-size: 10px;
    line-height: 1.6;
}

.settings-page .sp-options {
    display: grid;
    gap: 0;
}

.settings-page .sp-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid var(--sp-border);
    min-width: 0;
}

.settings-page .sp-option:first-child {
    padding-top: 2px;
}

.settings-page .sp-option:last-child {
    border-bottom: 0;
    padding-bottom: 2px;
}

.settings-page .sp-option-copy {
    min-width: 0;
}

.settings-page .sp-option-copy strong {
    display: block;
    color: var(--sp-text);
    font-size: 11px;
    font-weight: 750;
    margin-bottom: 4px;
}

.settings-page .sp-option-copy span {
    display: block;
    color: var(--sp-muted);
    font-size: 10px;
    line-height: 1.6;
}

.settings-page .sp-switch {
    position: relative;
    display: block;
    flex: 0 0 38px;
    width: 38px;
    height: 21px;
}

.settings-page .sp-switch input[type="checkbox"] {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
}

.settings-page .sp-switch-track {
    position: absolute;
    inset: 0;
    background: #aab5ac;
    border-radius: 20px;
    transition: .2s;
}

.settings-page .sp-switch-track::after {
    content: "";
    position: absolute;
    width: 15px;
    height: 15px;
    top: 3px;
    left: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .2s;
}

.settings-page .sp-switch input:checked + .sp-switch-track {
    background: var(--sp-primary);
}

.settings-page .sp-switch input:checked + .sp-switch-track::after {
    transform: translateX(17px);
}

.settings-page .sp-switch input:focus-visible + .sp-switch-track {
    outline: 3px solid var(--sp-primary);
    outline-offset: 3px;
}

.settings-page .sp-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
    border-top: 1px solid var(--sp-border);
    margin-top: 21px;
    padding-top: 16px;
}

.settings-page .sp-btn {
    min-height: 38px;
    padding: 9px 14px;
    border: 1px solid var(--sp-border);
    border-radius: 7px;
    background: var(--sp-card);
    color: var(--sp-text);
    font-size: 11px;
    font-weight: 750;
    transition: .18s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.settings-page .sp-btn:hover {
    background: var(--sp-soft);
}

.settings-page .sp-btn.primary {
    background: var(--sp-primary);
    color: #fff;
    border-color: var(--sp-primary);
}

.settings-page .sp-btn.primary:hover {
    background: var(--sp-primary-hover);
    border-color: var(--sp-primary-hover);
}

.settings-page .sp-btn.danger {
    color: var(--sp-danger);
    border-color: color-mix(in srgb, var(--sp-danger) 35%, var(--sp-border));
}

.settings-page .sp-table-wrap {
    max-width: 100%;
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--sp-border);
    border-radius: 8px;
    -webkit-overflow-scrolling: touch;
}

.settings-page .sp-table {
    width: 100%;
    min-width: 610px;
    border-collapse: collapse;
    color: var(--sp-text);
}

.settings-page .sp-table th {
    padding: 12px 13px;
    background: var(--sp-soft);
    color: var(--sp-muted);
    border-bottom: 1px solid var(--sp-border);
    text-align: left;
    white-space: nowrap;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .7px;
}

.settings-page .sp-table td {
    padding: 13px;
    border-bottom: 1px solid var(--sp-border);
    font-size: 11px;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

.settings-page .sp-table tbody tr:last-child td {
    border-bottom: 0;
}

.settings-page .sp-table tbody tr:hover {
    background: var(--sp-soft);
}

.settings-page .sp-table input,
.settings-page .sp-table select {
    min-height: 34px;
    max-width: 100%;
    padding: 7px;
    background: var(--sp-input);
    border: 1px solid var(--sp-border);
    border-radius: 6px;
    color: var(--sp-text);
    font-size: 11px;
}

.settings-page .sp-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 8px;
    border-radius: 4px;
    background: var(--sp-primary-soft);
    color: var(--sp-primary);
    font-size: 9px;
    font-weight: 800;
}

.settings-page .sp-note {
    background: var(--sp-soft);
    border: 1px solid var(--sp-border);
    border-left: 3px solid var(--sp-primary);
    padding: 13px 15px;
    border-radius: 6px;
    color: var(--sp-muted);
    font-size: 11px;
    line-height: 1.7;
    margin-bottom: 18px;
}

.settings-page .sp-note strong {
    color: var(--sp-text);
}

.settings-page .sp-empty {
    text-align: center;
    padding: 30px 15px;
    color: var(--sp-muted);
    font-size: 11px;
}

.settings-page .sp-role-permissions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.settings-page .sp-permission-group {
    min-width: 0;
    padding: 13px;
    border: 1px solid var(--sp-border);
    border-radius: 8px;
    background: var(--sp-soft);
}

.settings-page .sp-permission-group h4 {
    margin: 0 0 11px;
    font-size: 11px;
    font-weight: 800;
    color: var(--sp-text);
}

.settings-page .sp-check {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 9px 0;
    font-size: 10px;
    color: var(--sp-muted);
    line-height: 1.5;
}

.settings-page .sp-check input {
    accent-color: var(--sp-primary);
    margin-top: 2px;
    flex-shrink: 0;
}

.settings-page .sp-flash,
.settings-page .sp-error {
    border-radius: 7px;
    padding: 12px 14px;
    margin-bottom: 16px;
    font-size: 11px;
    line-height: 1.6;
}

.settings-page .sp-flash {
    color: #176b3a;
    background: #edf7ef;
    border: 1px solid #c5e6cc;
}

.settings-page .sp-error {
    color: #a22b2b;
    background: #fff1f1;
    border: 1px solid #f0caca;
}

.settings-page button:focus-visible,
.settings-page a:focus-visible {
    outline: 2px solid var(--sp-primary);
    outline-offset: 3px;
}

@media (max-width: 950px) {
    .settings-page .sp-layout {
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
    }

    .settings-page .sp-sidebar {
        position: static;
        padding: 8px;
    }

    .settings-page .sp-nav-heading {
        padding: 7px 9px;
    }

    .settings-page .sp-nav {
        display: flex;
        overflow-x: auto;
        gap: 5px;
        padding-bottom: 4px;
        scrollbar-width: thin;
    }

    .settings-page .sp-nav button {
        width: auto;
        flex: 0 0 auto;
        white-space: nowrap;
        min-height: 37px;
    }
}

@media (max-width: 600px) {
    .settings-page .sp-header {
        margin-bottom: 17px;
    }

    .settings-page .sp-header-meta {
        display: none;
    }

    .settings-page .sp-grid,
    .settings-page .sp-role-permissions {
        grid-template-columns: minmax(0, 1fr);
    }

    .settings-page .sp-field.full {
        grid-column: auto;
    }

    .settings-page .sp-card {
        padding: 15px;
        margin-bottom: 12px;
    }

    .settings-page .sp-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }

    .settings-page .sp-actions .sp-btn {
        width: 100%;
    }

    .settings-page .sp-table {
        min-width: 560px;
    }

    .settings-page .sp-nav button {
        font-size: 10px;
        padding: 9px 10px;
    }
}

@media (max-width: 380px) {
    .settings-page .sp-card {
        padding: 12px;
    }

    .settings-page .sp-heading h1 {
        font-size: 22px;
    }

    .settings-page .sp-option {
        gap: 8px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .settings-page *,
    .settings-page *::before,
    .settings-page *::after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}
</style>
@endpush

@section('content')
<div class="settings-page">

    <header class="sp-header">
        <div class="sp-heading">
            <div class="sp-eyebrow">Company administration</div>
            <h1>Settings</h1>
            <p>Manage your company's operational configuration, preferences and access policies.</p>
        </div>

        <div class="sp-header-meta">
            <span class="sp-status-dot"></span>
            Company configuration
        </div>
    </header>

    @if(session('success'))
        <div class="sp-flash" role="status">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="sp-error" role="alert">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="sp-error" role="alert">
            <strong>Please review the following errors:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="sp-layout">

        <aside class="sp-sidebar">
            <div class="sp-nav-heading">Configuration</div>

            <nav class="sp-nav" id="settingsNav" aria-label="Settings navigation">
                @foreach($sections as $key => $label)
                    <button
                        type="button"
                        data-panel="{{ $key }}"
                        class="{{ $loop->first ? 'active' : '' }}"
                        aria-controls="panel-{{ $key }}"
                        aria-current="{{ $loop->first ? 'page' : 'false' }}"
                    >
                        <span>{{ $label }}</span>
                        <span class="sp-nav-indicator"></span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <main class="sp-main">

            {{-- COMPANY PROFILE --}}
            <section class="sp-panel active" id="panel-company">
                <div class="sp-section-heading">
                    <h2>Company profile</h2>
                    <p>Manage the business information used across your company's POS environment.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Business information</h3>
                            <p>Update your company's identity and primary contact details.</p>
                        </div>
                        <span class="sp-card-label">General</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['company_name','company_email','company_phone','company_address']); @endphp

                        <div class="sp-grid">
                            <div class="sp-field">
                                <label for="company_name">Company name</label>
                                <input id="company_name" name="company_name" value="{{ $setting('company_name') }}" maxlength="150" required placeholder="Enter company name">
                            </div>

                            <div class="sp-field">
                                <label for="company_email">Business email</label>
                                <input id="company_email" type="email" name="company_email" value="{{ $setting('company_email') }}" placeholder="company@example.com">
                            </div>

                            <div class="sp-field">
                                <label for="company_phone">Business phone</label>
                                <input id="company_phone" type="tel" name="company_phone" value="{{ $setting('company_phone') }}" placeholder="+254...">
                            </div>

                            <div class="sp-field full">
                                <label for="company_address">Business address</label>
                                <textarea id="company_address" name="company_address" placeholder="Enter business address">{{ $setting('company_address') }}</textarea>
                            </div>
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save changes</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- REGIONAL PREFERENCES --}}
            <section class="sp-panel" id="panel-general">
                <div class="sp-section-heading">
                    <h2>Regional preferences</h2>
                    <p>Set the currency, timezone and date format used by your business.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Localization</h3>
                            <p>Configure how financial and date information is displayed.</p>
                        </div>
                        <span class="sp-card-label">Regional</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['currency','timezone','date_format']); @endphp

                        <div class="sp-grid">
                            <div class="sp-field">
                                <label for="currency">Business currency</label>
                                <select name="currency" id="currency">
                                    @foreach([
                                        'KES'=>'Kenyan Shilling (KES)',
                                        'USD'=>'US Dollar (USD)',
                                        'EUR'=>'Euro (EUR)',
                                        'GBP'=>'British Pound (GBP)',
                                        'UGX'=>'Ugandan Shilling (UGX)',
                                        'TZS'=>'Tanzanian Shilling (TZS)'
                                    ] as $code => $name)
                                        <option value="{{ $code }}" @selected($setting('currency','KES') === $code)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <div class="sp-hint">Used for displaying prices and transaction totals.</div>
                            </div>

                            <div class="sp-field">
                                <label for="timezone">Business timezone</label>
                                <select name="timezone" id="timezone">
                                    @foreach([
                                        'Africa/Nairobi',
                                        'Africa/Kampala',
                                        'Africa/Dar_es_Salaam',
                                        'Africa/Johannesburg',
                                        'UTC',
                                        'Europe/London',
                                        'America/New_York'
                                    ] as $zone)
                                        <option value="{{ $zone }}" @selected($setting('timezone','Africa/Nairobi') === $zone)>{{ $zone }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sp-field">
                                <label for="date_format">Date display format</label>
                                <select name="date_format" id="date_format">
                                    @foreach([
                                        'd/m/Y'=>'DD/MM/YYYY',
                                        'm/d/Y'=>'MM/DD/YYYY',
                                        'Y-m-d'=>'YYYY-MM-DD',
                                        'd M Y'=>'DD Mon YYYY'
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected($setting('date_format','d/m/Y') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save preferences</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- RECEIPTS --}}
            <section class="sp-panel" id="panel-receipt">
                <div class="sp-section-heading">
                    <h2>Receipt configuration</h2>
                    <p>Configure receipt printing and customer-facing receipt information.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Receipt layout</h3>
                            <p>Set the paper size and footer displayed on printed receipts.</p>
                        </div>
                        <span class="sp-card-label">Printing</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['receipt_size','receipt_footer','receipt_auto_print']); @endphp

                        <div class="sp-grid">
                            <div class="sp-field">
                                <label for="receipt_size">Paper size</label>
                                <select name="receipt_size" id="receipt_size">
                                    <option value="58mm" @selected($setting('receipt_size','80mm') === '58mm')>58mm thermal paper</option>
                                    <option value="80mm" @selected($setting('receipt_size','80mm') === '80mm')>80mm thermal paper</option>
                                </select>
                            </div>

                            <div class="sp-field full">
                                <label for="receipt_footer">Receipt footer</label>
                                <textarea name="receipt_footer" id="receipt_footer" placeholder="Thank you for shopping with us">{{ $setting('receipt_footer') }}</textarea>
                                <div class="sp-hint">This message is printed at the bottom of customer receipts.</div>
                            </div>
                        </div>

                        <div class="sp-options" style="margin-top:20px;">
                            <div class="sp-option">
                                <div class="sp-option-copy">
                                    <strong>Automatic receipt printing</strong>
                                    <span>Print a receipt automatically after a successful sale.</span>
                                </div>
                                <label class="sp-switch" aria-label="Automatic receipt printing">
                                    <input type="hidden" name="receipt_auto_print" value="0">
                                    <input type="checkbox" name="receipt_auto_print" value="1" @checked($isChecked('receipt_auto_print'))>
                                    <span class="sp-switch-track"></span>
                                </label>
                            </div>
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save receipt settings</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- PAYMENTS --}}
            <section class="sp-panel" id="panel-payments">
                <div class="sp-section-heading">
                    <h2>Payment methods</h2>
                    <p>Manage the payment methods available to employees during checkout.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Accepted payment methods</h3>
                            <p>Enable or disable the payment methods supported by your business.</p>
                        </div>
                        <span class="sp-card-label">Payments</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['allow_cash','allow_mpesa','allow_card']); @endphp

                        <div class="sp-options">
                            @foreach([
                                'allow_cash' => ['Cash payments','Allow cash transactions during checkout.'],
                                'allow_mpesa' => ['M-Pesa payments','Allow M-Pesa payments to be recorded.'],
                                'allow_card' => ['Card payments','Allow card transactions to be recorded.']
                            ] as $key => $details)
                                <div class="sp-option">
                                    <div class="sp-option-copy">
                                        <strong>{{ $details[0] }}</strong>
                                        <span>{{ $details[1] }}</span>
                                    </div>
                                    <label class="sp-switch" aria-label="{{ $details[0] }}">
                                        <input type="hidden" name="{{ $key }}" value="0">
                                        <input type="checkbox" name="{{ $key }}" value="1" @checked($isChecked($key, $key === 'allow_cash'))>
                                        <span class="sp-switch-track"></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save payment settings</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- TAX --}}
            <section class="sp-panel" id="panel-tax">
                <div class="sp-section-heading">
                    <h2>Tax configuration</h2>
                    <p>Manage the tax settings applied to eligible transactions.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Tax preferences</h3>
                            <p>Configure the tax name and applicable rate.</p>
                        </div>
                        <span class="sp-card-label">Financial</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['tax_enabled','tax_name','tax_rate']); @endphp

                        <div class="sp-options" style="margin-bottom:20px;">
                            <div class="sp-option">
                                <div class="sp-option-copy">
                                    <strong>Enable tax calculations</strong>
                                    <span>Apply the configured tax rate to applicable sales.</span>
                                </div>
                                <label class="sp-switch" aria-label="Enable tax calculations">
                                    <input type="hidden" name="tax_enabled" value="0">
                                    <input type="checkbox" name="tax_enabled" value="1" @checked($isChecked('tax_enabled'))>
                                    <span class="sp-switch-track"></span>
                                </label>
                            </div>
                        </div>

                        <div class="sp-grid">
                            <div class="sp-field">
                                <label for="tax_name">Tax name</label>
                                <input type="text" id="tax_name" name="tax_name" value="{{ $setting('tax_name','VAT') }}" placeholder="VAT">
                            </div>

                            <div class="sp-field">
                                <label for="tax_rate">Tax rate (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="{{ $setting('tax_rate','16') }}">
                            </div>
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save tax settings</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- INVENTORY --}}
            <section class="sp-panel" id="panel-inventory">
                <div class="sp-section-heading">
                    <h2>Inventory control</h2>
                    <p>Configure stock availability and inventory monitoring preferences.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Stock management</h3>
                            <p>Set the stock control behaviour for your sales operations.</p>
                        </div>
                        <span class="sp-card-label">Inventory</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['allow_negative_stock','low_stock_threshold']); @endphp

                        <div class="sp-options" style="margin-bottom:20px;">
                            <div class="sp-option">
                                <div class="sp-option-copy">
                                    <strong>Allow negative stock</strong>
                                    <span>Permit sales when the recorded available quantity is insufficient.</span>
                                </div>
                                <label class="sp-switch" aria-label="Allow negative stock">
                                    <input type="hidden" name="allow_negative_stock" value="0">
                                    <input type="checkbox" name="allow_negative_stock" value="1" @checked($isChecked('allow_negative_stock'))>
                                    <span class="sp-switch-track"></span>
                                </label>
                            </div>
                        </div>

                        <div class="sp-field">
                            <label for="low_stock_threshold">Low-stock threshold</label>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" min="0" step="1" value="{{ $setting('low_stock_threshold','5') }}">
                            <div class="sp-hint">Products at or below this quantity should be flagged for stock review.</div>
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save inventory settings</button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- ROLES --}}
            <section class="sp-panel" id="panel-roles">
                <div class="sp-section-heading">
                    <h2>Roles & permissions</h2>
                    <p>Define employee responsibilities and control access to company functions.</p>
                </div>

                @if($isOwner)
                    <div class="sp-card">
                        <div class="sp-card-top">
                            <div>
                                <h3>Create a role</h3>
                                <p>Create a new role and assign the appropriate permissions.</p>
                            </div>
                            <span class="sp-card-label">Access control</span>
                        </div>

                        <form method="POST" action="{{ route('settings.roles.store') }}">
                            @csrf

                            <div class="sp-field">
                                <label for="role_name">Role name</label>
                                <input type="text" id="role_name" name="name" maxlength="50" required placeholder="Enter role name">
                            </div>

                            @if(!empty($permissionGroups))
                                <div style="margin-top:22px;">
                                    <h3 style="font-size:12px;font-weight:800;margin:0 0 13px;">Permission assignments</h3>

                                    <div class="sp-role-permissions">
                                        @foreach($permissionGroups as $group => $permissions)
                                            <div class="sp-permission-group">
                                                <h4>{{ $group }}</h4>

                                                @foreach($permissions as $permissionName => $permissionLabel)
                                                    @php
                                                        if (is_int($permissionName)) {
                                                            $permissionName = is_object($permissionLabel)
                                                                ? $permissionLabel->name
                                                                : $permissionLabel;

                                                            $permissionLabel = str_replace(
                                                                ['.', '_'],
                                                                ' ',
                                                                ucfirst($permissionName)
                                                            );
                                                        }
                                                    @endphp

                                                    <label class="sp-check">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permissionName }}">
                                                        {{ $permissionLabel }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="sp-actions">
                                <button class="sp-btn primary" type="submit">Create role</button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="sp-note">
                        <strong>Restricted configuration.</strong>
                        Only the company owner can create roles and manage permissions.
                    </div>
                @endif

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Existing roles</h3>
                            <p>Review the access roles configured for this company.</p>
                        </div>
                        <span class="sp-card-label">{{ $roles->count() }} roles</span>
                    </div>

                    @if($roles->count())
                        <div class="sp-table-wrap">
                            <table class="sp-table">
                                <thead>
                                    <tr>
                                        <th>Role name</th>
                                        <th>Permissions</th>
                                        <th>Classification</th>
                                        @if($isOwner)
                                            <th>Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                        <tr>
                                            <td><strong>{{ $role->name }}</strong></td>
                                            <td>{{ $role->permissions->count() }} permissions</td>
                                            <td>
                                                @if(in_array($role->name, ['Company Owner','Admin']))
                                                    <span class="sp-badge">Protected</span>
                                                @else
                                                    <span class="sp-badge">Custom role</span>
                                                @endif
                                            </td>

                                            @if($isOwner)
                                                <td>
                                                    @if(!in_array($role->name, ['Company Owner','Admin']))
                                                        <form method="POST" action="{{ route('settings.roles.destroy', $role->id) }}" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="sp-btn danger">Delete</button>
                                                        </form>
                                                    @else
                                                        <span>Protected</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="sp-empty">No roles are currently configured.</div>
                    @endif
                </div>
            </section>

            {{-- STAFF --}}
            <section class="sp-panel" id="panel-staff">
                <div class="sp-section-heading">
                    <h2>Staff access</h2>
                    <p>Review employee access and manage assigned company roles.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Employee access management</h3>
                            <p>Review your company's registered staff and their current roles.</p>
                        </div>
                        <span class="sp-card-label">{{ $staff->count() }} employees</span>
                    </div>

                    @unless($isOwner)
                        <div class="sp-note">
                            Only the company owner can modify staff role assignments.
                        </div>
                    @endunless

                    @if($staff->count())
                        <div class="sp-table-wrap">
                            <table class="sp-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Email address</th>
                                        <th>Assigned role</th>
                                        @if($isOwner)
                                            <th>Update assignment</th>
                                        @endif
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($staff as $employee)
                                        @php
                                            $currentRole = $employee->getRoleNames()->first();
                                        @endphp

                                        <tr>
                                            <td><strong>{{ $employee->name }}</strong></td>
                                            <td>{{ $employee->email }}</td>
                                            <td>{{ $employee->getRoleNames()->join(', ') ?: 'Unassigned' }}</td>

                                            @if($isOwner)
                                                <td>
                                                    <form method="POST" action="{{ route('settings.staff.role', $employee->id) }}" style="display:flex;gap:7px;align-items:center;min-width:210px;">
                                                        @csrf
                                                        @method('PUT')

                                                        <select name="role" required aria-label="Role for {{ $employee->name }}">
                                                            @foreach($roles->where('name', '!=', 'Company Owner') as $role)
                                                                <option value="{{ $role->name }}" @selected($currentRole === $role->name)>
                                                                    {{ $role->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                        <button type="submit" class="sp-btn primary">Update</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="sp-empty">No staff members are currently available.</div>
                    @endif
                </div>
            </section>

            {{-- SECURITY --}}
            <section class="sp-panel" id="panel-security">
                <div class="sp-section-heading">
                    <h2>Security policies</h2>
                    <p>Configure company password requirements and session preferences.</p>
                </div>

                <div class="sp-card">
                    <div class="sp-card-top">
                        <div>
                            <h3>Account security</h3>
                            <p>Set the minimum password length and session timeout configuration.</p>
                        </div>
                        <span class="sp-card-label">Security</span>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        @php $hiddenSettings(['password_min_length','session_timeout']); @endphp

                        <div class="sp-grid">
                            <div class="sp-field">
                                <label for="password_min_length">Minimum password length</label>
                                <input type="number" id="password_min_length" name="password_min_length" min="8" max="64" value="{{ $setting('password_min_length','10') }}">
                                <div class="sp-hint">Minimum number of characters required for passwords.</div>
                            </div>

                            <div class="sp-field">
                                <label for="session_timeout">Session timeout (minutes)</label>
                                <input type="number" id="session_timeout" name="session_timeout" min="5" max="1440" value="{{ $setting('session_timeout','60') }}">
                                <div class="sp-hint">Configured session duration for company users.</div>
                            </div>
                        </div>

                        <div class="sp-actions">
                            <button class="sp-btn primary" type="submit">Save security settings</button>
                        </div>
                    </form>
                </div>
            </section>

        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const navigation = document.getElementById('settingsNav');

    if (!navigation) return;

    const buttons = navigation.querySelectorAll('[data-panel]');
    const panels = document.querySelectorAll('.settings-page .sp-panel');

    function activatePanel(name) {
        const target = document.getElementById('panel-' + name);

        if (!target) return;

        buttons.forEach(function (button) {
            const active = button.dataset.panel === name;

            button.classList.toggle('active', active);
            button.setAttribute('aria-current', active ? 'page' : 'false');
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('active', panel.id === 'panel-' + name);
        });

        try {
            sessionStorage.setItem('company-settings-active-panel', name);
        } catch (error) {
            // Navigation remains functional without session storage.
        }
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            activatePanel(button.dataset.panel);
        });
    });

    let initialPanel = 'company';

    @if($errors->any())
        initialPanel = @json($errorPanel);
    @else
        try {
            const savedPanel = sessionStorage.getItem('company-settings-active-panel');

            if (savedPanel && document.getElementById('panel-' + savedPanel)) {
                initialPanel = savedPanel;
            }
        } catch (error) {
            // Fall back to the company profile.
        }
    @endif

    activatePanel(initialPanel);
});
</script>
@endpush
