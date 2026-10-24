
@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-heading', 'Suppliers')

@section('content')

@php
    $suppliers = $suppliers ?? collect();

    $totalSuppliers = method_exists($suppliers, 'total')
        ? $suppliers->total()
        : $suppliers->count();

    $activeSuppliers = $suppliers->where('active', true)->count();
    $inactiveSuppliers = $suppliers->where('active', false)->count();
@endphp

<style>
    .suppliers-page {
        --sp-bg: #ffffff;
        --sp-soft: #f5f8f6;
        --sp-text: #17251d;
        --sp-muted: #718078;
        --sp-border: #e4ebe6;
        --sp-green: #15803d;
        --sp-green-soft: #eff7f1;
        color: var(--sp-text);
    }

    [data-theme="dark"] .suppliers-page,
    .dark .suppliers-page {
        --sp-bg: #17231b;
        --sp-soft: #1d3325;
        --sp-text: #e5eee8;
        --sp-muted: #9aaba0;
        --sp-border: #2a3a30;
        --sp-green: #4ade80;
        --sp-green-soft: #1d3325;
    }

    .suppliers-page .sp-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 22px;
    }

    .suppliers-page .sp-heading h2 {
        margin: 0 0 5px;
        color: var(--sp-text);
        font-size: 23px;
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .suppliers-page .sp-heading p {
        margin: 0;
        color: var(--sp-muted);
        font-size: 13px;
    }

    .suppliers-page .sp-alert {
        padding: 12px 15px;
        margin-bottom: 18px;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        font-size: 13px;
    }

    .suppliers-page .sp-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .suppliers-page .sp-stat {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--sp-border);
        border-radius: 13px;
        background: var(--sp-bg);
    }

    .suppliers-page .sp-stat-icon {
        display: grid;
        place-items: center;
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        border-radius: 11px;
        background: var(--sp-green-soft);
        color: var(--sp-green);
        font-size: 18px;
    }

    .suppliers-page .sp-stat-label {
        color: var(--sp-muted);
        font-size: 11px;
        margin-bottom: 4px;
    }

    .suppliers-page .sp-stat-value {
        color: var(--sp-text);
        font-size: 20px;
        font-weight: 750;
    }

    .suppliers-page .sp-card {
        overflow: hidden;
        margin-bottom: 20px;
        border: 1px solid var(--sp-border);
        border-radius: 14px;
        background: var(--sp-bg);
    }

    .suppliers-page .sp-card-header {
        padding: 17px 19px;
        border-bottom: 1px solid var(--sp-border);
    }

    .suppliers-page .sp-card-header h3 {
        margin: 0 0 4px;
        color: var(--sp-text);
        font-size: 14px;
        font-weight: 700;
    }

    .suppliers-page .sp-card-header p {
        margin: 0;
        color: var(--sp-muted);
        font-size: 12px;
    }

    .suppliers-page .sp-form-body {
        padding: 19px;
    }

    .suppliers-page .sp-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px;
    }

    .suppliers-page .sp-field {
        min-width: 0;
    }

    .suppliers-page .sp-field-full {
        grid-column: 1 / -1;
    }

    .suppliers-page label {
        display: block;
        margin-bottom: 7px;
        color: var(--sp-text);
        font-size: 12px;
        font-weight: 650;
    }

    .suppliers-page .sp-required {
        color: #dc2626;
    }

    .suppliers-page input {
        display: block;
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid var(--sp-border);
        border-radius: 9px;
        outline: none;
        background: var(--sp-bg);
        color: var(--sp-text);
        font-family: inherit;
        font-size: 13px;
        box-sizing: border-box;
        transition: .2s ease;
    }

    .suppliers-page input:focus {
        border-color: var(--sp-green);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--sp-green) 13%, transparent);
    }

    .suppliers-page input::placeholder {
        color: var(--sp-muted);
    }

    .suppliers-page .sp-error {
        margin-top: 6px;
        color: #dc2626;
        font-size: 12px;
    }

    .suppliers-page .sp-form-footer {
        display: flex;
        justify-content: flex-end;
        padding-top: 18px;
        margin-top: 18px;
        border-top: 1px solid var(--sp-border);
    }

    .suppliers-page .sp-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 41px;
        padding: 10px 16px;
        border: 1px solid var(--sp-green);
        border-radius: 9px;
        background: var(--sp-green);
        color: #fff;
        font-family: inherit;
        font-size: 13px;
        font-weight: 650;
        cursor: pointer;
    }

    .suppliers-page .sp-submit:hover {
        filter: brightness(.94);
    }

    .suppliers-page .sp-table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 17px 19px;
        border-bottom: 1px solid var(--sp-border);
    }

    .suppliers-page .sp-table-header h3 {
        margin: 0;
        color: var(--sp-text);
        font-size: 14px;
        font-weight: 700;
    }

    .suppliers-page .sp-table-header span {
        color: var(--sp-muted);
        font-size: 12px;
    }

    .suppliers-page .sp-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .suppliers-page table {
        width: 100%;
        min-width: 700px;
        border-collapse: collapse;
    }

    .suppliers-page th {
        padding: 13px 16px;
        background: var(--sp-soft);
        color: var(--sp-muted);
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }

    .suppliers-page td {
        padding: 14px 16px;
        border-top: 1px solid var(--sp-border);
        color: var(--sp-text);
        font-size: 13px;
        vertical-align: middle;
    }

    .suppliers-page tbody tr:hover {
        background: var(--sp-soft);
    }

    .suppliers-page .sp-supplier {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 150px;
    }

    .suppliers-page .sp-avatar {
        display: grid;
        place-items: center;
        width: 35px;
        height: 35px;
        flex-shrink: 0;
        border-radius: 10px;
        background: var(--sp-green-soft);
        color: var(--sp-green);
        font-size: 12px;
        font-weight: 750;
    }

    .suppliers-page .sp-name {
        color: var(--sp-text);
        font-weight: 650;
    }

    .suppliers-page .sp-contact {
        color: var(--sp-muted);
        font-size: 12px;
    }

    .suppliers-page .sp-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }

    .suppliers-page .sp-status-active {
        background: #dcfce7;
        color: #166534;
    }

    .suppliers-page .sp-status-inactive {
        background: #f1f5f9;
        color: #64748b;
    }

    .suppliers-page .sp-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 6px 11px;
        border: 1px solid var(--sp-border);
        border-radius: 8px;
        background: var(--sp-bg);
        color: var(--sp-text);
        font-family: inherit;
        font-size: 11px;
        font-weight: 650;
        cursor: pointer;
    }

    .suppliers-page .sp-toggle:hover {
        border-color: var(--sp-green);
        color: var(--sp-green);
    }

    .suppliers-page .sp-empty {
        padding: 45px 20px;
        text-align: center;
    }

    .suppliers-page .sp-empty-icon {
        display: grid;
        place-items: center;
        width: 50px;
        height: 50px;
        margin: 0 auto 12px;
        border-radius: 14px;
        background: var(--sp-soft);
        color: var(--sp-muted);
        font-size: 22px;
    }

    .suppliers-page .sp-empty h4 {
        margin: 0 0 5px;
        color: var(--sp-text);
        font-size: 14px;
    }

    .suppliers-page .sp-empty p {
        margin: 0;
        color: var(--sp-muted);
        font-size: 12px;
    }

    .suppliers-page .sp-pagination {
        padding: 15px 18px;
        border-top: 1px solid var(--sp-border);
    }

    @media (max-width: 700px) {
        .suppliers-page .sp-stats {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    @media (max-width: 560px) {
        .suppliers-page .sp-heading h2 {
            font-size: 20px;
        }

        .suppliers-page .sp-form-grid {
            grid-template-columns: 1fr;
        }

        .suppliers-page .sp-field-full {
            grid-column: auto;
        }

        .suppliers-page .sp-form-body {
            padding: 15px;
        }

        .suppliers-page .sp-submit {
            width: 100%;
        }
    }
</style>

<div class="suppliers-page">

    <div class="sp-header">
        <div class="sp-heading">
            <h2>Suppliers</h2>
            <p>Manage supplier contacts and account status.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="sp-alert">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="sp-alert" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;">
            Please correct the highlighted fields and try again.
        </div>
    @endif

    <div class="sp-stats">
        <div class="sp-stat">
            <div class="sp-stat-icon">♧</div>
            <div>
                <div class="sp-stat-label">Total suppliers on this page</div>
                <div class="sp-stat-value">{{ number_format($totalSuppliers) }}</div>
            </div>
        </div>

        <div class="sp-stat">
            <div class="sp-stat-icon">✓</div>
            <div>
                <div class="sp-stat-label">Active on this page</div>
                <div class="sp-stat-value">{{ number_format($activeSuppliers) }}</div>
            </div>
        </div>

        <div class="sp-stat">
            <div class="sp-stat-icon">○</div>
            <div>
                <div class="sp-stat-label">Inactive on this page</div>
                <div class="sp-stat-value">{{ number_format($inactiveSuppliers) }}</div>
            </div>
        </div>
    </div>

    <div class="sp-card">
        <div class="sp-card-header">
            <h3>Add a supplier</h3>
            <p>Enter the supplier's contact information below.</p>
        </div>

        <div class="sp-form-body">
            <form method="POST" action="{{ route('suppliers.store') }}">
                @csrf

                <div class="sp-form-grid">

                    <div class="sp-field">
                        <label for="name">
                            Supplier name <span class="sp-required">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            placeholder="Enter supplier name"
                            required
                            maxlength="255"
                            class="@error('name') sp-input-error @enderror"
                        >
                        @error('name')
                            <div class="sp-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="sp-field">
                        <label for="phone">Phone number</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone') }}"
                            placeholder="Enter phone number"
                            maxlength="50"
                        >
                        @error('phone')
                            <div class="sp-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="sp-field">
                        <label for="email">Email address</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            placeholder="supplier@example.com"
                            maxlength="255"
                        >
                        @error('email')
                            <div class="sp-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="sp-field">
                        <label for="address">Physical address</label>
                        <input
                            type="text"
                            name="address"
                            id="address"
                            value="{{ old('address') }}"
                            placeholder="Enter supplier address"
                            maxlength="255"
                        >
                        @error('address')
                            <div class="sp-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="sp-form-footer">
                    <button type="submit" class="sp-submit">
                        <span>+</span>
                        Add supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="sp-card">

        <div class="sp-table-header">
            <h3>Supplier directory</h3>
            <span>{{ number_format($suppliers->count()) }} records on this page</span>
        </div>

        <div class="sp-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($suppliers as $s)
                        @php
                            $initial = strtoupper(substr(trim($s->name ?? 'S'), 0, 1));
                        @endphp

                        <tr>
                            <td>
                                <div class="sp-supplier">
                                    <div class="sp-avatar">{{ $initial }}</div>
                                    <div class="sp-name">{{ $s->name }}</div>
                                </div>
                            </td>

                            <td>
                                <span class="sp-contact">
                                    {{ $s->phone ?: '—' }}
                                </span>
                            </td>

                            <td>
                                <span class="sp-contact">
                                    {{ $s->email ?: '—' }}
                                </span>
                            </td>

                            <td>
                                @if($s->active)
                                    <span class="sp-status sp-status-active">
                                        <span>●</span> Active
                                    </span>
                                @else
                                    <span class="sp-status sp-status-inactive">
                                        <span>●</span> Inactive
                                    </span>
                                @endif
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    action="{{ route('suppliers.toggle', $s) }}"
                                    onsubmit="return confirm('Are you sure you want to {{ $s->active ? 'deactivate' : 'activate' }} this supplier?');"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="sp-toggle">
                                        {{ $s->active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="sp-empty">
                                    <div class="sp-empty-icon">♧</div>
                                    <h4>No suppliers found</h4>
                                    <p>Add your first supplier using the form above.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($suppliers, 'links'))
            <div class="sp-pagination">
                {{ $suppliers->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
