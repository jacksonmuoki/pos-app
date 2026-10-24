@extends('super.layout')

@section('title', 'New company')
@section('breadcrumb', 'Companies / New')

@section('content')

<div class="page-heading">
    <div>
        <h1>New company</h1>
        <p>Register a new tenant workspace and provision its first administrator.</p>
    </div>
    <a href="{{ route('super.companies.index') }}" class="btn btn-light">
        ← Back to companies
    </a>
</div>

<form class="panel form-panel" method="POST" action="{{ route('super.companies.store') }}">
    @csrf

    <div class="form-head">
        <div class="form-head-icon">▦</div>
        <div>
            <h2>Company details</h2>
            <p>Creates the company's own database, tables, web address and first administrator in one step.</p>
        </div>
    </div>

    <div class="form-body">

        <!-- SECTION: COMPANY -->
        <div class="form-section">
            <div class="form-section-label">Company information</div>

            <div class="field-grid">
                <div class="field">
                    <label for="id">Company ID</label>
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
                    <span class="field-hint">Lowercase letters and numbers. It becomes the database name (tenant + id).</span>
                </div>

                <div class="field">
                    <label for="name">Company name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="{{ $errors->has('name') ? 'err' : '' }}"
                        placeholder="Acme Traders"
                        required
                    >
                </div>
            </div>

            <div class="field-grid">
                <div class="field">
                    <label for="domain">Domain</label>
                    <input
                        type="text"
                        id="domain"
                        name="domain"
                        value="{{ old('domain') }}"
                        class="{{ $errors->has('domain') ? 'err' : '' }}"
                        placeholder="acme.localhost"
                        required
                    >
                    <span class="field-hint">Staff sign in at this address. No http:// and no port.</span>
                </div>

                <div class="field">
                    <label for="plan">Plan</label>
                    <select id="plan" name="plan" required>
                        @foreach ($plans as $p)
                            <option value="{{ $p }}" @selected(old('plan', 'basic') === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                    <span class="field-hint">Used for your own records and reports.</span>
                </div>
            </div>

            <div class="field-grid">
                <div class="field">
                    <label for="contact_email">Contact email <span class="optional">(optional)</span></label>
                    <input
                        type="email"
                        id="contact_email"
                        name="contact_email"
                        value="{{ old('contact_email') }}"
                        class="{{ $errors->has('contact_email') ? 'err' : '' }}"
                        placeholder="info@acme.com"
                    >
                </div>

                <div class="field">
                    <label for="contact_phone">Contact phone <span class="optional">(optional)</span></label>
                    <input
                        type="text"
                        id="contact_phone"
                        name="contact_phone"
                        value="{{ old('contact_phone') }}"
                        class="{{ $errors->has('contact_phone') ? 'err' : '' }}"
                        placeholder="+254 700 000 000"
                    >
                </div>
            </div>
        </div>

        <!-- SECTION: ADMIN -->
        <div class="form-section">
            <div class="form-section-label">First administrator</div>
            <p class="form-section-desc">This account will have full access to the new company workspace.</p>

            <div class="field-grid">
                <div class="field">
                    <label for="admin_name">Full name</label>
                    <input
                        type="text"
                        id="admin_name"
                        name="admin_name"
                        value="{{ old('admin_name') }}"
                        class="{{ $errors->has('admin_name') ? 'err' : '' }}"
                        placeholder="Jane Doe"
                        required
                    >
                </div>

                <div class="field">
                    <label for="admin_email">Email address</label>
                    <input
                        type="email"
                        id="admin_email"
                        name="admin_email"
                        value="{{ old('admin_email') }}"
                        class="{{ $errors->has('admin_email') ? 'err' : '' }}"
                        placeholder="jane@acme.com"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label for="admin_password">Password</label>
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
                <span class="field-hint">Minimum 8 characters. You can reset it later from the company page.</span>
            </div>
        </div>

    </div>

    <div class="form-footer">
        <a href="{{ route('super.companies.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">Create company</button>
    </div>

</form>

@endsection

@push('styles')
<style>
    /* Aliases so this page works with the shared layout's variables */
    .form-panel {
        --ink: var(--text-primary);
        --muted: var(--text-muted);
        --line-soft: #f0f3f7;
    }

    .btn-light {
        background: #ffffff;
    }

    .form-panel {
        max-width: 700px;
        margin: 0 auto;
        overflow: hidden;
    }

    .form-head {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 22px 28px;
        border-bottom: 1px solid var(--line);
        background: #ffffff;
    }

    .form-head-icon {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: #f1f4f9;
        color: var(--ink);
        font-size: 18px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .form-head h2 {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.3px;
        margin: 0;
        color: var(--ink);
    }

    .form-head p {
        margin: 4px 0 0;
        font-size: 12px;
        color: var(--muted);
        font-weight: 500;
    }

    .form-body {
        padding: 24px 28px 4px;
    }

    .form-section {
        padding-bottom: 22px;
        margin-bottom: 22px;
        border-bottom: 1px solid var(--line-soft);
    }

    .form-section:last-child {
        border-bottom: 0;
        margin-bottom: 0;
    }

    .form-section-label {
        font-size: 12px;
        font-weight: 800;
        color: var(--muted);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section-label::after {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--line-soft);
    }

    .form-section-desc {
        font-size: 12px;
        color: var(--muted);
        font-weight: 500;
        margin: -8px 0 16px;
        line-height: 1.5;
    }

    .field {
        margin-bottom: 16px;
    }

    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 16px;
    }

    .field label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 6px;
    }

    .optional {
        color: var(--muted);
        font-weight: 500;
    }

    .field input,
    .field select {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid var(--border-mid);
        border-radius: var(--radius-sm);
        background: #fcfdfe;
        color: var(--ink);
        font: inherit;
        font-size: 13.5px;
        font-weight: 500;
        transition: border 0.15s, box-shadow 0.15s, background 0.15s;
        outline: none;
    }

    .field input::placeholder {
        color: #a6b2c0;
        font-weight: 400;
    }

    .field input:hover,
    .field select:hover {
        border-color: #bcc6d2;
    }

    .field input:focus,
    .field select:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    .field input.err {
        border-color: var(--red);
        background: #fffafa;
    }

    .field-hint {
        display: block;
        font-size: 11.5px;
        color: var(--muted);
        font-weight: 500;
        margin-top: 6px;
        line-height: 1.5;
    }

    .form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 18px 28px;
        border-top: 1px solid var(--line);
        background: #fafbfd;
    }

    @media (max-width: 600px) {
        .field-grid {
            grid-template-columns: 1fr;
        }
        .form-head,
        .form-body,
        .form-footer {
            padding-left: 20px;
            padding-right: 20px;
        }
        .form-footer {
            flex-direction: column-reverse;
        }
        .form-footer .btn {
            width: 100%;
        }
    }
</style>
@endpush