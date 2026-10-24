
@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-heading', 'Edit Employee')

@section('content')

<div class="edit-staff-page">

    <div class="edit-header">
        <div>
            <div class="eyebrow">COMPANY MANAGEMENT / STAFF</div>
            <h1>Edit Employee</h1>
            <p>Update employee information and assigned workspace role.</p>
        </div>

        <a href="{{ route('staff.index') }}" class="back-button">
            <span>←</span> Back to staff
        </a>
    </div>

    @if($errors->any())
        <div class="edit-alert">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="edit-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="edit-layout">

        <div class="edit-main">

            <div class="edit-card">

                <div class="section-heading">
                    <div>
                        <h2>Employee information</h2>
                        <p>Update the employee's account details below.</p>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('staff.update', $employee) }}"
                      class="edit-form">

                    @csrf
                    @method('PUT')

                    <div class="edit-form-grid">

                        <div class="edit-field">
                            <label for="name">Full name <span>*</span></label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $employee->name) }}"
                                maxlength="255"
                                placeholder="Enter employee's full name"
                                required
                            >

                            @error('name')
                                <small>{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="edit-field">
                            <label for="email">Email address <span>*</span></label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $employee->email) }}"
                                maxlength="255"
                                placeholder="employee@example.com"
                                required
                            >

                            @error('email')
                                <small>{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="edit-field full-width">
                            <label for="role">Employee role <span>*</span></label>

                            <select id="role" name="role" required>
                                <option value="">Select employee role</option>

                                @foreach($roles as $role)
                                    <option
                                        value="{{ $role->name }}"
                                        @selected(old('role', $employee->roles->first()?->name ?? $employee->role) === $role->name)
                                    >
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('role')
                                <small>{{ $message }}</small>
                            @enderror

                            <div class="field-hint">
                                The selected role determines the employee's assigned workspace permissions.
                            </div>
                        </div>

                    </div>

                    <div class="pin-notice">
                        <div class="pin-notice-title">Existing sign-in PIN</div>
                        <p>
                            This employee's current PIN will remain unchanged.
                            Saving these details will not reset their login credentials.
                        </p>
                    </div>

                    <div class="form-footer">
                        <a href="{{ route('staff.index') }}" class="cancel-button">
                            Cancel
                        </a>

                        <button type="submit" class="save-button">
                            Save changes
                        </button>
                    </div>

                </form>

            </div>

        </div>

        <aside class="edit-sidebar">

            <div class="profile-card">
                <div class="profile-avatar">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>

                <h3>{{ $employee->name }}</h3>
                <p>{{ $employee->email }}</p>

                <div class="profile-status">
                    @if($employee->active)
                        <span class="status-dot active"></span>
                        Active account
                    @else
                        <span class="status-dot inactive"></span>
                        Inactive account
                    @endif
                </div>
            </div>

            <div class="information-card">
                <h3>Account information</h3>

                <div class="information-row">
                    <span>Account ID</span>
                    <strong>#{{ $employee->id }}</strong>
                </div>

                <div class="information-row">
                    <span>Date created</span>
                    <strong>{{ $employee->created_at?->format('d M Y') ?? '—' }}</strong>
                </div>

                <div class="information-row">
                    <span>Last updated</span>
                    <strong>{{ $employee->updated_at?->format('d M Y') ?? '—' }}</strong>
                </div>

                <div class="information-row">
                    <span>Account status</span>
                    <strong>{{ $employee->active ? 'Active' : 'Inactive' }}</strong>
                </div>
            </div>

        </aside>

    </div>

</div>
@endsection

@push('styles')
<style>
.edit-staff-page {
    width:100%;
    color:var(--text, #172b24);
}

.edit-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:25px;
}

.eyebrow {
    color:#15803d;
    font-size:10px;
    font-weight:800;
    letter-spacing:1.2px;
    margin-bottom:8px;
}

.edit-header h1 {
    font-size:26px;
    line-height:1.2;
    font-weight:780;
    letter-spacing:-.6px;
    margin:0;
}

.edit-header p {
    color:var(--text-2, #718078);
    font-size:12px;
    margin:7px 0 0;
}

.back-button {
    display:inline-flex;
    align-items:center;
    gap:9px;
    padding:10px 14px;
    border:1px solid var(--border, #e5eee8);
    border-radius:8px;
    color:var(--text, #34483c);
    background:var(--surface, #fff);
    font-size:11px;
    font-weight:700;
    text-decoration:none;
    white-space:nowrap;
}

.back-button:hover {
    background:var(--surface-2, #f8faf9);
    color:#166534;
}

.back-button span {
    font-size:17px;
}

.edit-layout {
    display:grid;
    grid-template-columns:minmax(0, 1fr) 285px;
    gap:20px;
    align-items:start;
}

.edit-card,
.profile-card,
.information-card {
    background:var(--surface, #fff);
    border:1px solid var(--border, #e5eee8);
    border-radius:12px;
    box-shadow:0 2px 8px rgba(15,50,30,.025);
}

.edit-card {
    overflow:hidden;
}

.section-heading {
    padding:21px 23px;
    border-bottom:1px solid var(--border, #edf2ee);
}

.section-heading h2 {
    font-size:14px;
    font-weight:750;
    margin:0;
}

.section-heading p {
    font-size:11px;
    color:var(--text-2, #849188);
    margin:5px 0 0;
}

.edit-form {
    padding:23px;
}

.edit-form-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:21px;
}

.edit-field {
    display:flex;
    flex-direction:column;
    gap:8px;
    min-width:0;
}

.edit-field.full-width {
    grid-column:1 / -1;
}

.edit-field label {
    color:var(--text, #34483c);
    font-size:11px;
    font-weight:750;
}

.edit-field label span {
    color:#dc2626;
}

.edit-field input,
.edit-field select {
    width:100%;
    height:43px;
    border:1px solid var(--border, #dfe8e2);
    border-radius:7px;
    padding:0 12px;
    background:var(--surface, #fff);
    color:var(--text, #172b24);
    font-family:inherit;
    font-size:12px;
    outline:none;
    transition:.2s ease;
}

.edit-field input:focus,
.edit-field select:focus {
    border-color:#16a34a;
    box-shadow:0 0 0 3px rgba(22,163,74,.09);
}

.edit-field input::placeholder {
    color:#a0aaa3;
}

.edit-field small {
    font-size:10px;
    color:#dc2626;
}

.field-hint {
    font-size:10px;
    color:var(--text-3, #849188);
    line-height:1.6;
}

.pin-notice {
    margin-top:24px;
    padding:15px 16px;
    border:1px solid #dcfce7;
    border-radius:8px;
    background:#f0fdf4;
}

.pin-notice-title {
    font-size:11px;
    color:#166534;
    font-weight:750;
}

.pin-notice p {
    color:#53715d;
    font-size:11px;
    line-height:1.6;
    margin:6px 0 0;
}

.form-footer {
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:10px;
    border-top:1px solid var(--border, #edf2ee);
    margin-top:25px;
    padding-top:19px;
}

.cancel-button,
.save-button {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:39px;
    padding:0 17px;
    border-radius:7px;
    font-family:inherit;
    font-size:11px;
    font-weight:750;
    text-decoration:none;
    cursor:pointer;
    transition:.2s ease;
}

.cancel-button {
    background:var(--surface, #fff);
    color:var(--text-2, #64756b);
    border:1px solid var(--border, #dfe8e2);
}

.cancel-button:hover {
    background:var(--surface-2, #f8faf9);
}

.save-button {
    border:1px solid #166534;
    background:#166534;
    color:#fff;
}

.save-button:hover {
    background:#14532d;
}

.edit-sidebar {
    display:flex;
    flex-direction:column;
    gap:15px;
}

.profile-card {
    padding:24px 17px;
    text-align:center;
}

.profile-avatar {
    width:58px;
    height:58px;
    margin:0 auto 13px;
    display:grid;
    place-items:center;
    border-radius:15px;
    background:#dcfce7;
    color:#166534;
    font-size:23px;
    font-weight:800;
}

.profile-card h3 {
    color:var(--text, #172b24);
    font-size:14px;
    font-weight:750;
    margin:0;
}

.profile-card > p {
    color:var(--text-2, #849188);
    font-size:10px;
    margin:6px 0 15px;
    overflow-wrap:anywhere;
}

.profile-status {
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:7px 11px;
    border-radius:20px;
    background:var(--surface-2, #f8faf9);
    color:var(--text-2, #64756b);
    font-size:10px;
    font-weight:700;
}

.status-dot {
    width:7px;
    height:7px;
    border-radius:50%;
}

.status-dot.active {
    background:#16a34a;
}

.status-dot.inactive {
    background:#94a3a8;
}

.information-card {
    padding:19px;
}

.information-card h3 {
    font-size:12px;
    font-weight:750;
    margin:0 0 15px;
    color:var(--text, #172b24);
}

.information-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:11px 0;
    border-bottom:1px solid var(--border, #edf2ee);
}

.information-row:last-child {
    border-bottom:0;
    padding-bottom:0;
}

.information-row span {
    color:var(--text-2, #849188);
    font-size:10px;
}

.information-row strong {
    color:var(--text, #34483c);
    font-size:10px;
    font-weight:700;
    text-align:right;
}

.edit-alert,
.edit-success {
    padding:13px 16px;
    border-radius:8px;
    margin-bottom:17px;
    font-size:12px;
}

.edit-alert {
    color:#b42318;
    background:#fff7f7;
    border:1px solid #fecaca;
}

.edit-alert ul {
    padding-left:18px;
    margin:7px 0 0;
}

.edit-success {
    color:#166534;
    background:#f0fdf4;
    border:1px solid #bbf7d0;
}

@media(max-width:900px) {
    .edit-layout {
        grid-template-columns:minmax(0,1fr);
    }

    .edit-sidebar {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:600px) {
    .edit-header {
        align-items:flex-start;
        flex-direction:column;
    }

    .edit-header h1 {
        font-size:23px;
    }

    .edit-form-grid {
        grid-template-columns:1fr;
    }

    .edit-field.full-width {
        grid-column:auto;
    }

    .edit-form,
    .section-heading {
        padding:17px;
    }

    .edit-sidebar {
        grid-template-columns:1fr;
    }

    .form-footer {
        flex-direction:column-reverse;
    }

    .cancel-button,
    .save-button {
        width:100%;
    }
}
</style>
@endpush
