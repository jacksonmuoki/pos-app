
@extends('layouts.app')

@section('title', 'Staff Management')
@section('page-heading', 'Staff Management')

@php
    $user = auth()->user();
@endphp

@section('content')

<div class="staff-page">

    {{-- PAGE HEADER --}}
    <div class="staff-header">
        <div>
            <div class="eyebrow">COMPANY MANAGEMENT</div>
            <h1>Staff Management</h1>
            <p>Manage employee accounts, roles and workspace access.</p>
        </div>

        <a href="#add-employee" class="primary-button">
            <span>+</span> Add employee
        </a>
    </div>

    {{-- STATISTICS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top">
                <span>Total employees</span>
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M16 21V19C16 16.79 14.21 15 12 15H6C3.79 15 2 16.79 2 19V21"
                              stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M20 21V19C20 17.2 18.8 15.7 17.2 15.2M16 3.2C17.8 3.7 19 5.2 19 7S17.8 10.3 16 10.8"
                              stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value">{{ number_format($totalStaff) }}</div>
            <div class="stat-caption">Registered company accounts</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Active employees</span>
                <div class="stat-icon success">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 12.5L10 17L20 7" stroke="currentColor"
                              stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value">{{ number_format($activeStaff) }}</div>
            <div class="stat-caption">Accounts currently enabled</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Inactive employees</span>
                <div class="stat-icon muted">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M12 8V12M12 16H12.01" stroke="currentColor"
                              stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value">{{ number_format($inactiveStaff) }}</div>
            <div class="stat-caption">Accounts currently disabled</div>
        </div>
    </div>

    {{-- NOTIFICATIONS --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span class="alert-symbol">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            <span class="alert-symbol">!</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <div>
                <strong>Please correct the following:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ADD EMPLOYEE --}}
    <div class="content-card add-employee-panel" id="add-employee">

        <div class="card-heading">
            <div class="heading-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 5V19M5 12H19" stroke="currentColor"
                          stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="heading-copy">
                <h2>Add a new employee</h2>
                <p>Create an employee account and assign their workspace role.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('staff.store') }}" class="employee-form">
            @csrf

            <div class="form-grid">

                <div class="form-group">
                    <label for="name">Full name <span>*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Enter employee's full name"
                        maxlength="255"
                        required
                    >
                    @error('name')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email address <span>*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="employee@example.com"
                        maxlength="255"
                        required
                    >
                    @error('email')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="role">Employee role <span>*</span></label>
                    <select id="role" name="role" required>
                        <option value="">Select employee role</option>

                        @foreach($roles as $role)
                            @if($role->name !== 'Company Owner' && ($role->name !== 'Admin' || auth()->user()->isCompanyOwner()))
                                <option
                                    value="{{ $role->name }}"
                                    @selected(old('role') === $role->name)
                                >
                                    {{ $role->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('role')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="pin">Employee PIN <span>*</span></label>
                    <input
                        type="password"
                        id="pin"
                        name="pin"
                        placeholder="Create a 4–6 digit PIN"
                        inputmode="numeric"
                        pattern="[0-9]{4,6}"
                        minlength="4"
                        maxlength="6"
                        required
                        autocomplete="new-password"
                    >
                    <small class="field-hint">Use 4–6 digits. This PIN will be used to sign in.</small>
                    @error('pin')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="pin_confirmation">Confirm PIN <span>*</span></label>
                    <input
                        type="password"
                        id="pin_confirmation"
                        name="pin_confirmation"
                        placeholder="Enter the PIN again"
                        inputmode="numeric"
                        pattern="[0-9]{4,6}"
                        minlength="4"
                        maxlength="6"
                        required
                        autocomplete="new-password"
                    >
                    @error('pin_confirmation')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

            </div>

            <div class="form-footer">
                <div class="form-note">
                    <span class="note-dot"></span>
                    New employee accounts will be created as active accounts.
                </div>

                <button type="submit" class="submit-button">
                    Create employee <span>→</span>
                </button>
            </div>
        </form>
    </div>

    {{-- EMPLOYEE DIRECTORY --}}
    <div class="content-card staff-panel">

        <div class="card-heading directory-heading">
            <div>
                <h2>Employee directory</h2>
                <p>View and manage employees registered in this company.</p>
            </div>

            <span class="record-count">
                {{ number_format($staff->total()) }} records
            </span>
        </div>

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('staff.index') }}" class="filter-bar">

            <div class="search-field">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M16 16L21 21" stroke="currentColor"
                          stroke-width="1.7" stroke-linecap="round"/>
                </svg>

                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by name or email..."
                >
            </div>

            <select name="role" aria-label="Filter by role">
                <option value="">All roles</option>
                @foreach($roles as $role)
                    <option
                        value="{{ $role->name }}"
                        @selected($selectedRole === $role->name)
                    >
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>

            <button type="submit" class="filter-button">Apply filters</button>
            <a href="{{ route('staff.index') }}" class="reset-button">Reset</a>

        </form>

        {{-- TABLE --}}
        <div class="table-scroll">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>ACCOUNT ROLE</th>
                        <th>ACCESS ROLES</th>
                        <th>STATUS</th>
                        <th>DATE ADDED</th>
                        <th class="actions-heading">ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($staff as $member)
                        <tr>
                            <td>
                                <div class="employee-cell">
                                    <div class="employee-avatar">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>

                                    <div class="employee-details">
                                        <div class="employee-name">{{ $member->name }}</div>
                                        <div class="employee-email">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="account-role">
                                    {{ ucfirst($member->role ?? 'N/A') }}
                                </span>
                            </td>

                            <td>
                                @forelse($member->roles as $assignedRole)
                                    <span class="role-chip">{{ $assignedRole->name }}</span>
                                @empty
                                    <span class="not-assigned">No role assigned</span>
                                @endforelse
                            </td>

                            <td>
                                @if($member->active)
                                    <span class="status-pill active">
                                        <span></span> Active
                                    </span>
                                @else
                                    <span class="status-pill inactive">
                                        <span></span> Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="date-cell">
                                {{ $member->created_at?->format('d M Y') ?? '—' }}
                            </td>

                            <td>
                                <div class="staff-actions">

                                    @if(!$member->is($user) && !$member->isCompanyOwner())
                                        <a href="{{ route('staff.edit', $member) }}"
                                           class="action-button action-edit">
                                            Edit
                                        </a>

                                        <form method="POST"
                                              action="{{ route('staff.status', $member) }}"
                                              onsubmit="return confirm('Are you sure you want to {{ $member->active ? 'deactivate' : 'activate' }} this employee?');">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="action-button action-status">
                                                {{ $member->active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('staff.destroy', $member) }}"
                                              onsubmit="return confirm('Permanently delete this employee account? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="action-button action-delete">
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="protected-label">
                                            Protected account
                                        </span>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <h3>No employees found</h3>
                                    <p>Try changing your search or filter criteria.</p>
                                    <a href="{{ route('staff.index') }}">Clear filters</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($staff->hasPages())
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Showing {{ $staff->firstItem() }}–{{ $staff->lastItem() }}
                    of {{ $staff->total() }} employees
                </div>

                {{ $staff->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

@push('styles')
<style>
.staff-page {
    width:100%;
    color:var(--text, #172b24);
}

.staff-header {
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
    letter-spacing:1.3px;
    margin-bottom:8px;
}

.staff-header h1 {
    font-size:27px;
    line-height:1.2;
    letter-spacing:-.7px;
    font-weight:780;
    margin:0;
    color:var(--text, #142b20);
}

.staff-header p {
    font-size:12px;
    color:var(--text-2, #718078);
    margin:7px 0 0;
}

.primary-button {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    padding:11px 16px;
    border-radius:8px;
    background:var(--primary, #15803d);
    color:#fff;
    text-decoration:none;
    font-size:12px;
    font-weight:700;
    transition:.2s ease;
    white-space:nowrap;
}

.primary-button:hover {
    background:#166534;
    color:#fff;
    transform:translateY(-1px);
}

.primary-button span {
    font-size:19px;
    line-height:12px;
}

.stats-grid {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
    margin-bottom:22px;
}

.stat-card,
.content-card {
    background:var(--surface, #fff);
    border:1px solid var(--border, #e5eee8);
    border-radius:12px;
    box-shadow:0 2px 8px rgba(15,50,30,.025);
}

.stat-card {
    padding:19px 20px;
}

.stat-top {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    font-size:12px;
    color:var(--text-2, #718078);
    font-weight:600;
}

.stat-icon {
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    background:#f0fdf4;
    color:#166534;
    border-radius:9px;
}

.stat-icon.success {
    background:#dcfce7;
    color:#15803d;
}

.stat-icon.muted {
    background:#f1f5f3;
    color:#64756b;
}

.stat-icon svg {
    width:18px;
    height:18px;
}

.stat-value {
    color:var(--text, #172b24);
    font-size:28px;
    font-weight:800;
    letter-spacing:-1px;
    margin-top:12px;
}

.stat-caption {
    font-size:11px;
    color:var(--text-3, #87938c);
    margin-top:4px;
}

.alert {
    display:flex;
    align-items:flex-start;
    gap:11px;
    padding:13px 15px;
    border-radius:9px;
    margin-bottom:17px;
    font-size:12px;
}

.alert-success {
    color:#166534;
    background:#f0fdf4;
    border:1px solid #bbf7d0;
}

.alert-error {
    color:#b42318;
    background:#fff7f7;
    border:1px solid #fecaca;
}

.alert-symbol {
    font-weight:800;
    font-size:15px;
}

.alert ul {
    margin:7px 0 0;
    padding-left:18px;
}

.content-card {
    overflow:hidden;
    margin-bottom:22px;
}

.card-heading {
    display:flex;
    align-items:center;
    gap:13px;
    padding:20px 22px;
    border-bottom:1px solid var(--border, #edf2ee);
}

.heading-icon {
    width:37px;
    height:37px;
    display:grid;
    place-items:center;
    flex-shrink:0;
    color:#166534;
    background:#dcfce7;
    border-radius:10px;
}

.heading-icon svg {
    width:19px;
    height:19px;
}

.heading-copy h2,
.directory-heading h2 {
    font-size:14px;
    font-weight:750;
    margin:0;
    color:var(--text, #172b24);
}

.heading-copy p,
.directory-heading p {
    font-size:11px;
    color:var(--text-2, #849188);
    margin:5px 0 0;
}

.employee-form {
    padding:21px 22px;
}

.form-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:19px;
}

.form-group {
    display:flex;
    flex-direction:column;
    gap:8px;
    min-width:0;
}

.form-group label {
    font-size:11px;
    color:var(--text, #34483c);
    font-weight:700;
}

.form-group label span {
    color:#dc2626;
}

.form-group input,
.form-group select,
.filter-bar select {
    width:100%;
    height:41px;
    border:1px solid var(--border, #dfe8e2);
    border-radius:7px;
    padding:0 12px;
    background:var(--surface, #fff);
    color:var(--text, #172b24);
    font-family:inherit;
    font-size:12px;
    outline:none;
    transition:border-color .2s, box-shadow .2s;
}

.form-group input:focus,
.form-group select:focus,
.filter-bar select:focus {
    border-color:#16a34a;
    box-shadow:0 0 0 3px rgba(22,163,74,.09);
}

.form-group input::placeholder {
    color:#a0aaa3;
}

.field-hint {
    color:var(--text-3, #87938c);
    font-size:10px;
}

.field-error {
    color:#dc2626;
    font-size:10px;
}

.form-footer {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    border-top:1px solid var(--border, #edf2ee);
    margin-top:22px;
    padding-top:18px;
}

.form-note {
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--text-2, #87938c);
    font-size:10px;
}

.note-dot {
    width:7px;
    height:7px;
    border-radius:50%;
    background:#16a34a;
    flex-shrink:0;
}

.submit-button {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    border:0;
    border-radius:7px;
    padding:11px 17px;
    background:var(--primary, #166534);
    color:#fff;
    font-family:inherit;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
    transition:.2s ease;
}

.submit-button:hover {
    background:#14532d;
    transform:translateY(-1px);
}

.submit-button span {
    font-size:16px;
}

.directory-heading {
    justify-content:space-between;
    border-bottom:0;
}

.record-count {
    font-size:10px;
    color:#166534;
    background:#f0fdf4;
    border:1px solid #dcfce7;
    padding:7px 10px;
    border-radius:7px;
    white-space:nowrap;
    font-weight:700;
}

.filter-bar {
    display:flex;
    align-items:center;
    gap:9px;
    padding:0 22px 18px;
    flex-wrap:wrap;
}

.search-field {
    height:38px;
    display:flex;
    align-items:center;
    gap:9px;
    border:1px solid var(--border, #dfe8e2);
    border-radius:7px;
    padding:0 11px;
    flex:1;
    min-width:190px;
    background:var(--surface, #fff);
}

.search-field svg {
    width:16px;
    height:16px;
    color:var(--text-3, #87938c);
    flex-shrink:0;
}

.search-field input {
    width:100%;
    border:0;
    outline:0;
    font-family:inherit;
    font-size:11px;
    color:var(--text, #172b24);
    background:transparent;
}

.search-field input::placeholder {
    color:#a0aaa3;
}

.filter-bar select {
    width:auto;
    min-width:125px;
    height:38px;
    font-size:11px;
}

.filter-button {
    height:38px;
    background:var(--primary, #166534);
    color:#fff;
    border:0;
    border-radius:7px;
    padding:0 14px;
    font-family:inherit;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
    transition:.2s ease;
}

.filter-button:hover {
    background:#14532d;
}

.reset-button {
    font-size:11px;
    color:var(--text-2, #64756b);
    padding:9px 4px;
    text-decoration:none;
}

.reset-button:hover {
    color:#166534;
}

.table-scroll {
    width:100%;
    overflow-x:auto;
}

.staff-table {
    width:100%;
    border-collapse:collapse;
    min-width:1000px;
}

.staff-table thead {
    background:var(--surface-2, #f8faf9);
}

.staff-table th {
    padding:12px 15px;
    text-align:left;
    font-size:9px;
    letter-spacing:.7px;
    color:var(--text-3, #849188);
    font-weight:750;
    border-top:1px solid var(--border, #edf2ee);
    border-bottom:1px solid var(--border, #edf2ee);
    white-space:nowrap;
}

.staff-table td {
    padding:14px 15px;
    border-bottom:1px solid var(--border, #edf2ee);
    font-size:11px;
    color:var(--text-2, #46564d);
    vertical-align:middle;
}

.staff-table tbody tr:last-child td {
    border-bottom:0;
}

.staff-table tbody tr:hover {
    background:var(--surface-2, #fbfdfb);
}

.employee-cell {
    display:flex;
    align-items:center;
    gap:11px;
}

.employee-avatar {
    width:36px;
    height:36px;
    border-radius:10px;
    background:#dcfce7;
    color:#166534;
    display:grid;
    place-items:center;
    font-weight:800;
    font-size:13px;
    flex-shrink:0;
}

.employee-name {
    font-size:11px;
    font-weight:750;
    color:var(--text, #172b24);
}

.employee-email {
    font-size:10px;
    color:var(--text-3, #849188);
    margin-top:4px;
}

.account-role {
    font-weight:650;
    color:var(--text, #34483c);
}

.role-chip {
    display:inline-block;
    background:#f0fdf4;
    color:#166534;
    border:1px solid #dcfce7;
    padding:5px 8px;
    border-radius:5px;
    font-size:10px;
    font-weight:650;
    margin:2px 3px 2px 0;
}

.not-assigned {
    color:var(--text-3, #9aa69e);
    font-size:10px;
}

.status-pill {
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:20px;
    padding:5px 9px;
    font-size:10px;
    font-weight:700;
    white-space:nowrap;
}

.status-pill span {
    width:6px;
    height:6px;
    border-radius:50%;
    background:currentColor;
}

.status-pill.active {
    background:#dcfce7;
    color:#166534;
}

.status-pill.inactive {
    background:#f1f5f3;
    color:#64756b;
}

.date-cell {
    color:var(--text-2, #718078)!important;
    white-space:nowrap;
}

/* STAFF ACTIONS */

.actions-heading {
    min-width:225px;
}

.staff-actions {
    display:flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
}

.staff-actions form {
    margin:0;
}

.action-button {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:7px 9px;
    border-radius:6px;
    font-family:inherit;
    font-size:10px;
    font-weight:700;
    text-decoration:none;
    border:1px solid transparent;
    cursor:pointer;
    transition:background .2s ease, border-color .2s ease;
}

.action-edit {
    background:#eff6ff;
    color:#1d4ed8;
    border-color:#dbeafe;
}

.action-edit:hover {
    background:#dbeafe;
    color:#1e40af;
}

.action-status {
    background:#f0fdf4;
    color:#166534;
    border-color:#dcfce7;
}

.action-status:hover {
    background:#dcfce7;
}

.action-delete {
    background:#fff1f2;
    color:#be123c;
    border-color:#ffe4e6;
}

.action-delete:hover {
    background:#ffe4e6;
}

.protected-label {
    color:var(--text-3, #849188);
    font-size:10px;
    font-weight:600;
}

.empty-state {
    text-align:center;
    padding:45px 15px;
}

.empty-state h3 {
    font-size:14px;
    margin:0;
    color:var(--text, #172b24);
}

.empty-state p {
    font-size:11px;
    color:var(--text-2, #849188);
    margin:7px 0 13px;
}

.empty-state a {
    font-size:11px;
    color:#15803d;
    font-weight:700;
}

.pagination-wrap {
    padding:15px 20px;
    border-top:1px solid var(--border, #edf2ee);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}

.pagination-info {
    font-size:10px;
    color:var(--text-2, #849188);
}

.pagination-wrap nav {
    font-size:11px;
}

.pagination-wrap nav a,
.pagination-wrap nav span {
    display:inline-block;
    padding:7px 10px;
    border:1px solid var(--border, #e5eee8);
    border-radius:6px;
    margin:2px;
    color:var(--text-2, #46564d);
    text-decoration:none;
}

.pagination-wrap nav a:hover {
    color:#166534;
    border-color:#16a34a;
}

.pagination-wrap nav [aria-current="page"] span {
    background:#166534;
    color:#fff;
    border-color:#166534;
}

@media(max-width:850px) {
    .stats-grid {
        gap:10px;
    }

    .stat-card {
        padding:15px;
    }
}

@media(max-width:600px) {
    .staff-header {
        align-items:flex-start;
        flex-direction:column;
    }

    .staff-header h1 {
        font-size:23px;
    }

    .stats-grid {
        grid-template-columns:1fr;
    }

    .form-grid {
        grid-template-columns:1fr;
    }

    .employee-form {
        padding:17px 14px;
    }

    .form-footer {
        align-items:flex-start;
        flex-direction:column;
    }

    .submit-button {
        width:100%;
    }

    .filter-bar {
        padding:0 14px 15px;
    }

    .search-field {
        flex-basis:100%;
    }

    .filter-bar select {
        flex:1;
        min-width:0;
    }

    .filter-button {
        flex:1;
    }

    .card-heading {
        padding:17px 14px;
    }

    .pagination-wrap {
        align-items:flex-start;
        flex-direction:column;
    }
}
</style>
@endpush
