
@extends('super.layout')

@section('title', 'Company Settings')
@section('breadcrumb', 'Company Settings')

@section('content')

<style>
    .company-settings-page {
        color: #1e293b;
    }

    .company-settings-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .company-settings-heading h1 {
        margin: 0;
        font-size: 23px;
        font-weight: 800;
        color: #1e293b;
        font-family: 'Manrope', sans-serif;
    }

    .company-settings-heading p {
        margin: 6px 0 0;
        color: #7b8a9c;
        font-size: 12px;
    }

    .company-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 13px;
        border: 1px solid #e1e6ed;
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s ease;
    }

    .company-back:hover {
        color: #128d51;
        border-color: #128d51;
        background: #f0fdf4;
    }

    .company-settings-card {
        background: #fff;
        border: 1px solid #e6eaef;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.03);
    }

    .company-card-heading {
        padding: 20px 24px;
        border-bottom: 1px solid #e6eaef;
        background: #fafbfd;
    }

    .company-card-heading h2 {
        margin: 0;
        color: #1e293b;
        font-size: 15px;
        font-weight: 800;
    }

    .company-card-heading p {
        margin: 5px 0 0;
        color: #7b8a9c;
        font-size: 12px;
    }

    .company-settings-body {
        padding: 25px;
    }

    .company-maintenance {
        margin-top: 25px;
        padding-top: 23px;
        border-top: 1px solid #e6eaef;
    }

    .company-maintenance-heading {
        margin-bottom: 18px;
    }

    .company-maintenance-heading h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        color: #1e293b;
    }

    .company-maintenance-heading p {
        margin: 6px 0 0;
        color: #7b8a9c;
        font-size: 12px;
    }

    .company-maintenance-toggle {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 16px;
        border: 1px solid #e6eaef;
        background: #fafbfd;
        border-radius: 9px;
        margin-bottom: 18px;
    }

    .company-maintenance-toggle input {
        width: 17px;
        height: 17px;
        margin: 1px 0 0;
        accent-color: #128d51;
        cursor: pointer;
        flex-shrink: 0;
    }

    .company-maintenance-toggle label {
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        line-height: 1.6;
    }

    .company-maintenance-toggle small {
        display: block;
        color: #7b8a9c;
        font-weight: 400;
        margin-top: 4px;
    }

    .company-maintenance-field label {
        display: block;
        margin-bottom: 8px;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .company-maintenance-field textarea {
        width: 100%;
        min-height: 105px;
        padding: 12px;
        border: 1px solid #e1e6ed;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-size: 12px;
        outline: none;
        resize: vertical;
        box-sizing: border-box;
        line-height: 1.6;
        transition: .2s ease;
    }

    .company-maintenance-field textarea:focus {
        border-color: #128d51;
        box-shadow: 0 0 0 3px rgba(18,141,81,.08);
    }

    .company-settings-footer {
        display: flex;
        justify-content: flex-end;
        padding: 18px 25px;
        border-top: 1px solid #e6eaef;
        background: #fafbfd;
    }

    .company-save-btn {
        height: 41px;
        padding: 0 22px;
        border: none;
        border-radius: 8px;
        background: #128d51;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: .2s ease;
    }

    .company-save-btn:hover {
        background: #0e7542;
    }

    .company-save-btn:disabled {
        opacity: .7;
        cursor: not-allowed;
    }

    .company-alert {
        padding: 13px 15px;
        margin-bottom: 18px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.6;
    }

    .company-alert.success {
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        color: #166534;
    }

    .company-alert.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
    }

    @media(max-width: 520px) {
        .company-settings-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .company-settings-body {
            padding: 18px;
        }

        .company-card-heading {
            padding: 18px;
        }

        .company-settings-footer {
            padding: 15px 18px;
        }

        .company-save-btn {
            width: 100%;
        }
    }
</style>

<div class="company-settings-page">

    {{-- PAGE HEADING --}}
    <div class="company-settings-heading">
        <div>
            <h1>Company Settings</h1>
            <p>Manage individual company configuration and operational access.</p>
        </div>

        <a href="{{ route('super.settings') }}#companies" class="company-back">
            ← Back to companies
        </a>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="company-alert success">
            {{ session('success') }}
        </div>
    @endif

    {{-- VALIDATION ERRORS --}}
    @if($errors->any())
        <div class="company-alert error">
            <strong>Please correct the following errors:</strong>

            <ul style="margin:8px 0 0;padding-left:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="company-settings-card">

        <div class="company-card-heading">
            <h2>Company #{{ $company->id }}</h2>
            <p>Configure this company's business information, integrations and availability.</p>
        </div>

        <form
            method="POST"
            action="{{ route('super.company-settings.update', $company) }}"
            id="company-settings-form"
        >

            @csrf
            @method('PUT')

            <div class="company-settings-body">

                {{-- EXISTING COMPANY SETTINGS FIELDS --}}
                @include('partials.company-settings-fields')

                {{-- COMPANY MAINTENANCE --}}
                <div class="company-maintenance">

                    <div class="company-maintenance-heading">
                        <h2>Company maintenance</h2>
                        <p>Control maintenance access for this company only.</p>
                    </div>

                    <div class="company-maintenance-toggle">

                        <input
                            type="checkbox"
                            id="maintenance_mode"
                            name="maintenance_mode"
                            value="1"
                            @checked((string) old('maintenance_mode', $values['maintenance_mode'] ?? '0') === '1')
                        >

                        <label for="maintenance_mode">
                            Put this company in maintenance mode

                            <small>
                                Restrict access to this company without affecting other companies on the platform.
                            </small>
                        </label>

                    </div>

                    <div class="company-maintenance-field">
                        <label for="maintenance_message">
                            Maintenance message
                        </label>

                        <textarea
                            id="maintenance_message"
                            name="maintenance_message"
                            rows="4"
                            maxlength="500"
                            placeholder="Enter the message displayed to company staff..."
                        >{{ old('maintenance_message', $values['maintenance_message'] ?? '') }}</textarea>

                        <small style="display:block;margin-top:7px;color:#94a3b8;font-size:11px">
                            Maximum 500 characters. This message will be shown to staff when maintenance is active.
                        </small>
                    </div>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="company-settings-footer">

                <button
                    type="submit"
                    class="company-save-btn"
                    id="company-save-btn"
                >
                    Save company settings
                </button>

            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('company-settings-form');

    if (form) {
        form.addEventListener('submit', function () {

            const button = document.getElementById('company-save-btn');

            if (button) {
                button.disabled = true;
                button.textContent = 'Saving settings...';
            }

        });
    }

});
</script>

@endsection
