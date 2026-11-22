
@extends('super.layout')

@section('title', 'New company')
@section('breadcrumb', 'Companies / New')

@section('content')
<div class="new-company-page">

    <header class="page-heading">
        <div>
            <h1>New company</h1>
            <p>Register a new tenant workspace and provision its first administrator.</p>
        </div>
        <a href="{{ route('super.companies.index') }}" class="nc-btn nc-btn-light">
            &larr; Back to companies
        </a>
    </header>

    <form class="nc-panel" method="POST" action="{{ route('super.companies.store') }}">
        @csrf

        <div class="nc-form-head">
            <div class="nc-form-icon" aria-hidden="true">▦</div>
            <div>
                <h2>Company details</h2>
                <p>Creates the company's own database, tables, web address and first administrator in one step.</p>
            </div>
        </div>

        <div class="nc-form-body">

            {{-- COMPANY INFORMATION --}}
            <section class="nc-section">
                <div class="nc-section-title">Company information</div>

                <div class="nc-grid">
                    <div class="nc-field">
                        <label for="id">Company ID <span class="required">*</span></label>
                        <input
                            type="text"
                            id="id"
                            name="id"
                            value="{{ old('id') }}"
                            class="{{ $errors->has('id') ? 'err' : '' }}"
                            placeholder="acme"
                            required
                            autofocus
                            pattern="[a-z0-9]{2,30}"
                            title="2 to 30 lowercase letters and numbers"
                        >
                        @error('id')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                        <small class="nc-hint">Lowercase letters and numbers. It becomes the database name (tenant + id).</small>
                    </div>

                    <div class="nc-field">
                        <label for="name">Company name <span class="required">*</span></label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="{{ $errors->has('name') ? 'err' : '' }}"
                            placeholder="Acme Traders"
                            required
                        >
                        @error('name')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="nc-field">
                        <label for="domain">Domain <span class="required">*</span></label>
                        <input
                            type="text"
                            id="domain"
                            name="domain"
                            value="{{ old('domain') }}"
                            class="{{ $errors->has('domain') ? 'err' : '' }}"
                            placeholder="acme.localhost"
                            required
                        >
                        @error('domain')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                        <small class="nc-hint">Staff sign in at this address. No http:// and no port.</small>
                    </div>

                    <div class="nc-field">
                        <label for="plan">Plan <span class="required">*</span></label>
                        <select id="plan" name="plan" required>
                            @foreach ($plans as $p)
                                <option value="{{ $p }}" @selected(old('plan', 'basic') === $p)>
                                    {{ ucfirst($p) }}
                                </option>
                            @endforeach
                        </select>
                        @error('plan')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                        <small class="nc-hint">Used for your own records and reports.</small>
                    </div>

                    <div class="nc-field">
                        <label for="contact_email">Contact email <span class="optional">(optional)</span></label>
                        <input
                            type="email"
                            id="contact_email"
                            name="contact_email"
                            value="{{ old('contact_email') }}"
                            class="{{ $errors->has('contact_email') ? 'err' : '' }}"
                            placeholder="info@acme.com"
                        >
                        @error('contact_email')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="nc-field">
                        <label for="contact_phone">Contact phone <span class="optional">(optional)</span></label>
                        <input
                            type="text"
                            id="contact_phone"
                            name="contact_phone"
                            value="{{ old('contact_phone') }}"
                            class="{{ $errors->has('contact_phone') ? 'err' : '' }}"
                            placeholder="+254 700 000 000"
                        >
                        @error('contact_phone')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- FIRST ADMINISTRATOR --}}
            <section class="nc-section">
                <div class="nc-section-title">First administrator</div>
                <p class="nc-section-desc">
                    This account will have full access to the new company workspace.
                </p>

                <div class="nc-grid">
                    <div class="nc-field">
                        <label for="admin_name">Full name <span class="required">*</span></label>
                        <input
                            type="text"
                            id="admin_name"
                            name="admin_name"
                            value="{{ old('admin_name') }}"
                            class="{{ $errors->has('admin_name') ? 'err' : '' }}"
                            placeholder="Jane Doe"
                            required
                        >
                        @error('admin_name')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="nc-field">
                        <label for="admin_email">Email address <span class="required">*</span></label>
                        <input
                            type="email"
                            id="admin_email"
                            name="admin_email"
                            value="{{ old('admin_email') }}"
                            class="{{ $errors->has('admin_email') ? 'err' : '' }}"
                            placeholder="jane@acme.com"
                            required
                        >
                        @error('admin_email')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="nc-field nc-field-full">
                        <label for="admin_password">Password <span class="required">*</span></label>
                        <input
                            type="password"
                            id="admin_password"
                            name="admin_password"
                            class="{{ $errors->has('admin_password') ? 'err' : '' }}"
                            placeholder="At least 8 characters"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        @error('admin_password')
                            <small class="nc-error">{{ $message }}</small>
                        @enderror
                        <small class="nc-hint">Minimum 8 characters. You can reset it later from the company page.</small>
                    </div>
                </div>
            </section>

        </div>

        <footer class="nc-form-footer">
            <a href="{{ route('super.companies.index') }}" class="nc-btn nc-btn-light">
                Cancel
            </a>
            <button type="submit" class="nc-btn nc-btn-primary">
                Create company <span aria-hidden="true">&rarr;</span>
            </button>
        </footer>
    </form>
</div>
@endsection

@push('styles')

<style>
    .new-company-page {
        --nc-primary: #128d51;
        --nc-primary-dark: #0f7545;
        --nc-ink: #1e293b;
        --nc-muted: #7b8a9c;
        --nc-border: #e6eaef;

        width: 100%;
        max-width: 100%;
        min-width: 0;
        color: var(--nc-ink);
        font-family: 'Manrope', sans-serif;
        overflow-x: clip;
    }

    .new-company-page *,
    .new-company-page *::before,
    .new-company-page *::after {
        box-sizing: border-box;
    }

    /* PAGE HEADER */
    .new-company-page .page-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        width: 100%;
        margin-bottom: 20px;
    }

    .new-company-page .page-heading > div {
        min-width: 0;
        flex: 1 1 250px;
    }

    .new-company-page .page-heading h1 {
        margin: 0 0 5px;
        font-size: clamp(18px, 2vw, 22px);
        font-weight: 700;
        letter-spacing: -.4px;
        overflow-wrap: anywhere;
    }

    .new-company-page .page-heading p {
        margin: 0;
        color: var(--nc-muted);
        font-size: 12px;
        line-height: 1.6;
    }

    /* MAIN FORM */
    .new-company-page .nc-panel {
    width: 100%;
    max-width: none;
    min-width: 0;
    margin: 0;
    background: #fff;
    border: 1px solid #e6eaef;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    overflow: hidden;
}

    .new-company-page .nc-form-head {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--nc-border);
    }

    .new-company-page .nc-form-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: rgba(18, 141, 81, .09);
        color: var(--nc-primary);
        font-size: 19px;
        font-weight: 700;
    }

    .new-company-page .nc-form-head > div:last-child {
        min-width: 0;
    }

    .new-company-page .nc-form-head h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
    }

    .new-company-page .nc-form-head p {
        margin: 4px 0 0;
        color: var(--nc-muted);
        font-size: 11.5px;
        line-height: 1.6;
    }

    .new-company-page .nc-form-body {
        width: 100%;
        min-width: 0;
        padding: 24px;
    }

    /* SECTIONS */
    .new-company-page .nc-section {
        width: 100%;
        min-width: 0;
        padding-bottom: 22px;
        margin-bottom: 20px;
        border-bottom: 1px solid #edf0f3;
    }

    .new-company-page .nc-section:last-child {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: 0;
    }

    .new-company-page .nc-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .055em;
        text-transform: uppercase;
    }

    .new-company-page .nc-section-title::after {
        content: "";
        height: 1px;
        flex: 1;
        background: #edf0f3;
    }

    .new-company-page .nc-section-desc {
        margin: -8px 0 16px;
        color: var(--nc-muted);
        font-size: 12px;
        line-height: 1.6;
    }

    /* RESPONSIVE GRID */
    .new-company-page .nc-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 24px;
    width: 100%;
    min-width: 0;
}

    .new-company-page .nc-field {
        width: 100%;
        min-width: 0;
        margin-bottom: 17px;
    }

    .new-company-page .nc-field-full {
        grid-column: 1 / -1;
    }

    .new-company-page .nc-field label {
        display: block;
        margin-bottom: 7px;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
    }

    .new-company-page .required {
        color: #dc2626;
    }

    .new-company-page .optional {
        color: var(--nc-muted);
        font-weight: 400;
    }

    /* INPUTS */
    .new-company-page .nc-field input,
    .new-company-page .nc-field select {
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        height: 44px;
        padding: 0 12px;
        border: 1px solid var(--nc-border);
        border-radius: 8px;
        outline: none;
        background: #fff;
        color: var(--nc-ink);
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .new-company-page .nc-field input::placeholder {
        color: #a3adba;
        font-weight: 400;
    }

    .new-company-page .nc-field input:hover,
    .new-company-page .nc-field select:hover {
        border-color: #cbd5e1;
    }

    .new-company-page .nc-field input:focus,
    .new-company-page .nc-field select:focus {
        border-color: var(--nc-primary);
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .10);
    }

    .new-company-page .nc-field input.err,
    .new-company-page .nc-field select.err {
        border-color: #dc2626;
        background: #fffafa;
    }

    .new-company-page .nc-hint {
        display: block;
        margin-top: 6px;
        color: var(--nc-muted);
        font-size: 10.5px;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .new-company-page .nc-error {
        display: block;
        margin-top: 5px;
        color: #b42318;
        font-size: 11px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    /* FOOTER */
    .new-company-page .nc-form-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        padding: 17px 24px;
        border-top: 1px solid var(--nc-border);
        background: #fafbfc;
    }

    /* BUTTONS */
    .new-company-page .nc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 0 16px;
        border: 1px solid var(--nc-border);
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        white-space: normal;
        text-align: center;
        transition: .15s ease;
    }

    .new-company-page .nc-btn-light:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .new-company-page .nc-btn-primary {
        background: var(--nc-primary);
        border-color: var(--nc-primary);
        color: #fff;
    }

    .new-company-page .nc-btn-primary:hover {
        background: var(--nc-primary-dark);
        border-color: var(--nc-primary-dark);
    }

    .new-company-page .nc-btn:focus-visible {
        outline: 2px solid var(--nc-primary);
        outline-offset: 3px;
    }

    /* TABLETS */
    @media (max-width: 768px) {
        .new-company-page .nc-panel {
            max-width: 100%;
        }

        .new-company-page .nc-form-head {
            padding: 18px;
        }

        .new-company-page .nc-form-body {
            padding: 20px 18px;
        }

        .new-company-page .nc-form-footer {
            padding: 15px 18px;
        }
    }

    /* MOBILE PHONES */
    @media (max-width: 600px) {
        .new-company-page .page-heading {
            align-items: stretch;
            flex-direction: column;
            gap: 12px;
        }

        .new-company-page .page-heading > div {
            flex-basis: auto;
        }

        .new-company-page .page-heading .nc-btn {
            align-self: flex-start;
        }

        .new-company-page .nc-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 0;
        }

        .new-company-page .nc-field-full {
            grid-column: auto;
        }

        .new-company-page .nc-form-head {
            align-items: flex-start;
            padding: 16px;
            gap: 11px;
        }

        .new-company-page .nc-form-icon {
            width: 38px;
            height: 38px;
            flex-basis: 38px;
        }

        .new-company-page .nc-form-head h2 {
            font-size: 14px;
        }

        .new-company-page .nc-form-head p {
            font-size: 11px;
        }

        .new-company-page .nc-form-body {
            padding: 18px 16px;
        }

        .new-company-page .nc-field {
            margin-bottom: 16px;
        }

        .new-company-page .nc-field input,
        .new-company-page .nc-field select {
            height: 45px;
            font-size: 14px;
        }

        .new-company-page .nc-form-footer {
            display: flex;
            flex-direction: column-reverse;
            align-items: stretch;
            padding: 14px 16px;
        }

        .new-company-page .nc-form-footer .nc-btn {
            width: 100%;
            min-height: 43px;
        }
    }

    /* VERY SMALL PHONES */
    @media (max-width: 360px) {
        .new-company-page .page-heading h1 {
            font-size: 18px;
        }

        .new-company-page .nc-form-head {
            padding: 13px;
        }

        .new-company-page .nc-form-body {
            padding: 15px 12px;
        }

        .new-company-page .nc-form-footer {
            padding: 12px;
        }

        .new-company-page .nc-section-title {
            font-size: 10px;
        }

        .new-company-page .nc-field input,
        .new-company-page .nc-field select {
            font-size: 13px;
        }
    }
</style>
@endpush
