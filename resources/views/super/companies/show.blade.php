@extends('super.layout')
@section('title', $label ?? ($tenant->name ?: $tenant->id))

@section('content')
@php
    $label     = $tenant->name ?: $tenant->id;
    $initial   = mb_strtoupper(mb_substr($label, 0, 1));
    $first     = $tenant->domains->first();
    $planLabel = ucfirst($tenant->plan ?: 'basic');
@endphp

<style>
    /* ---- Crumb --------------------------------------------------------- */
    .crumb {
        display: inline-block;
        margin-bottom: 14px;
        color: #6b7280;
        font-size: 12.5px;
        text-decoration: none;
    }
    .crumb:hover { color: #2563eb; }

    /* ---- Page heading -------------------------------------------------- */
    .page-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .heading-identity {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }
    .symbol-lg {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        font-weight: 600;
        background: rgba(37, 99, 235, .08);
        color: #2563eb;
        flex: 0 0 auto;
    }
    .page-heading h1 {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 4px;
        letter-spacing: -0.01em;
        line-height: 1.25;
    }
    .meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-size: 12.5px;
        color: #6b7280;
    }
    .meta .dot { opacity: .55; }
    .meta .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11.5px;
    }
    .actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    /* ---- Alerts -------------------------------------------------------- */
    .alert {
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 16px;
    }
    .alert.warn {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }
    .alert.bad {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    /* ---- Stat grid ----------------------------------------------------- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 14px 16px;
    }
    .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 500;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #6b7280;
    }
    .stat-icon {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        background: #f3f4f6;
        color: #374151;
    }
    .stat-icon.green { background: #dcfce7; color: #166534; }
    .stat-icon.blue  { background: #dbeafe; color: #1e40af; }
    .stat-icon.amber { background: #fef3c7; color: #92400e; }
    .stat-number {
        font-size: 22px;
        font-weight: 600;
        color: #111827;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }
    .stat-number.sm { font-size: 18px; }
    .stat-caption {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }

    /* ---- Two-column layout --------------------------------------------- */
    .cols {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    .cols > div > * + * { margin-top: 16px; }

    /* ---- Panels -------------------------------------------------------- */
    .panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }
    .panel.danger { border-color: #fecaca; }
    .panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
    }
    .panel-heading h2,
    .panel-heading h3 {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }
    .panel-heading p {
        margin: 2px 0 0;
        font-size: 12px;
        color: #6b7280;
    }
    .panel-body { padding: 16px 18px; }
    .panel-body + .panel-body { border-top: 1px solid #e5e7eb; }

    /* ---- Tables -------------------------------------------------------- */
    .table-wrap { overflow-x: auto; }
    table.data {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    table.data thead th {
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #6b7280;
        padding: 10px 14px;
        background: #fafafa;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    table.data thead th.num { text-align: right; }
    table.data tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    table.data tbody td.num {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    table.data tbody tr:last-child td { border-bottom: 0; }

    .cell-main {
        font-weight: 500;
        color: #111827;
    }
    .cell-sub {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }
    .cell-sub.mono,
    td.mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11.5px;
    }

    /* ---- Badges -------------------------------------------------------- */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
    }
    .badge.plan      { background: #eef2ff; color: #3730a3; }
    .badge.role      { background: #f3f4f6; color: #374151; }
    .badge.active    { background: #dcfce7; color: #166534; }
    .badge.suspended { background: #fee2e2; color: #991b1b; }
    .badge.info      { background: #dbeafe; color: #1e40af; }
    .badge.bad       { background: #fef3c7; color: #92400e; }

    /* ---- Buttons ------------------------------------------------------- */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 36px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #111827;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
        white-space: nowrap;
        font-family: inherit;
    }
    .btn:hover { background: #f9fafb; border-color: #cbd5e1; }
    .btn:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .btn[disabled],
    .btn:disabled {
        opacity: .55;
        cursor: not-allowed;
        background: #f9fafb;
    }
    .btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
    .btn-warn {
        background: #fff;
        border-color: #f59e0b;
        color: #92400e;
    }
    .btn-warn:hover { background: #fffbeb; }
    .btn-danger {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
    }
    .btn-danger:hover { background: #b91c1c; border-color: #b91c1c; }
    .btn-small {
        height: 28px;
        padding: 0 10px;
        font-size: 12px;
    }

    /* Inline forms (no layout shift) */
    form.inline {
        display: inline-flex;
        margin: 0;
    }
    td.num form.inline + form.inline { margin-left: 4px; }

    /* ---- Fields -------------------------------------------------------- */
    .field { margin-bottom: 12px; }
    .field:last-of-type { margin-bottom: 16px; }
    .field label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }
    .field input[type="text"],
    .field input[type="email"],
    .field input[type="password"],
    .field select,
    .field textarea {
        width: 100%;
        height: 36px;
        padding: 0 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        font-size: 13px;
        color: inherit;
        font-family: inherit;
        box-sizing: border-box;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .field textarea {
        height: auto;
        min-height: 80px;
        padding: 10px 12px;
        resize: vertical;
        line-height: 1.5;
    }
    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
    }
    .field input.err,
    .field select.err,
    .field textarea.err {
        border-color: #dc2626;
    }
    .field input.err:focus,
    .field select.err:focus,
    .field textarea.err:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, .15);
    }
    .field small {
        display: block;
        margin-top: 4px;
        font-size: 11.5px;
        color: #6b7280;
        line-height: 1.4;
    }
    .field small.error {
        color: #b91c1c;
        font-weight: 500;
    }
    .grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    /* ---- Disclosure (details/summary) --------------------------------- */
    details.disclose { border-top: 1px solid #e5e7eb; }
    details.disclose > summary {
        list-style: none;
        cursor: pointer;
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 500;
        color: #2563eb;
        user-select: none;
    }
    details.disclose > summary::-webkit-details-marker { display: none; }
    details.disclose > summary::before {
        content: "+";
        display: inline-block;
        width: 12px;
        margin-right: 6px;
        transition: transform .15s ease;
    }
    details.disclose[open] > summary::before { content: "−"; }

    /* ---- Rows (domains, history) --------------------------------------- */
    .row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .row:last-of-type { border-bottom: 0; }
    .row .grow {
        flex: 1 1 auto;
        min-width: 0;
        word-break: break-word;
    }
    .row .grow strong { font-weight: 500; color: #111827; }
    .row .cell-sub { margin-top: 2px; }

    .domain-add-form {
        display: flex;
        gap: 10px;
        align-items: stretch;
        border-top: 1px solid #e5e7eb;
        padding: 14px 18px;
    }
    .domain-add-form input {
        flex: 1 1 auto;
        height: 36px;
        padding: 0 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        font-family: inherit;
        min-width: 0;
    }
    .domain-add-form input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
    }
    .domain-add-form input.err { border-color: #dc2626; }
    .domain-error {
        padding: 0 18px 14px;
        font-size: 11.5px;
        color: #b91c1c;
    }

    /* ---- Text link ----------------------------------------------------- */
    .text-link {
        color: #2563eb;
        text-decoration: none;
    }
    .text-link:hover { text-decoration: underline; }

    /* ---- Empty state --------------------------------------------------- */
    .empty-state {
        padding: 28px 20px;
        text-align: center;
        color: #6b7280;
        font-size: 13px;
    }
    .empty-state strong {
        display: block;
        color: #111827;
        font-size: 14px;
        margin-bottom: 4px;
    }
    .empty-state p { margin: 0; }
    .empty-state.compact { padding: 24px 18px; }

    /* ---- Utility ------------------------------------------------------- */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* ---- Responsive ---------------------------------------------------- */
    @media (max-width: 1000px) {
        .cols { grid-template-columns: 1fr; }
        .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .stat-grid { grid-template-columns: 1fr; }
        .grid-2 { grid-template-columns: 1fr; }
        .page-heading { flex-direction: column; align-items: stretch; }
        .actions { justify-content: flex-start; }
        .row { padding: 10px 14px; }
        .panel-heading, .panel-body, .domain-add-form { padding-left: 14px; padding-right: 14px; }
    }
</style>

<a class="crumb" href="{{ route('super.companies.index') }}">&larr; Companies</a>

<header class="page-heading">
    <div class="heading-identity">
        <div class="symbol-lg" aria-hidden="true">{{ $initial }}</div>
        <div>
            <h1>{{ $label }}</h1>
            <p class="meta">
                <span class="mono">{{ $tenant->id }}</span>
                <span class="dot" aria-hidden="true">&middot;</span>
                <span class="badge plan">{{ $planLabel }}</span>
                <span class="badge {{ $tenant->suspended ? 'suspended' : 'active' }}">
                    {{ $tenant->suspended ? 'Suspended' : 'Active' }}
                </span>
                <span class="dot" aria-hidden="true">&middot;</span>
                <span>created {{ $tenant->created_at?->format('d M Y') }}</span>
            </p>
        </div>
    </div>
    <div class="actions">
        @if ($first)
            <a class="btn" href="{{ \App\Support\Fmt::url($first->domain) }}" target="_blank" rel="noopener">Open site</a>
        @endif
        <form class="inline" method="POST" action="{{ route('super.companies.migrate', $tenant->id) }}">
            @csrf
            <button class="btn" type="submit">Run migrations</button>
        </form>
        @if ($tenant->suspended)
            <form class="inline" method="POST" action="{{ route('super.companies.activate', $tenant->id) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Reactivate</button>
            </form>
        @endif
    </div>
</header>

@if ($tenant->suspended)
    <div class="alert warn" role="status">
        Suspended {{ $tenant->suspended_at ? \Carbon\Carbon::parse($tenant->suspended_at)->diffForHumans() : '' }}.
        @if ($tenant->suspended_reason) Reason: {{ $tenant->suspended_reason }} @endif
        Staff cannot sign in until you reactivate this company.
    </div>
@endif

@unless ($s['ok'])
    <div class="alert bad" role="alert">
        This company's database could not be read: {{ $s['error'] }}. Try "Run migrations".
    </div>
@endunless

<section class="stat-grid" aria-label="Company statistics">
    <div class="stat-card">
        <div class="stat-top"><span>Revenue</span><div class="stat-icon green" aria-hidden="true">&#164;</div></div>
        <div class="stat-number sm">{{ \App\Support\Fmt::money($s['revenue']) }}</div>
        <div class="stat-caption">{{ \App\Support\Fmt::money($s['today']) }} today</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Orders</span><div class="stat-icon blue" aria-hidden="true">&#9776;</div></div>
        <div class="stat-number">{{ number_format($s['orders']) }}</div>
        <div class="stat-caption">Last: {{ \App\Support\Fmt::ago($s['last_order']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Products</span><div class="stat-icon amber" aria-hidden="true">&#9635;</div></div>
        <div class="stat-number">{{ number_format($s['products']) }}</div>
        <div class="stat-caption">{{ $s['low_stock'] }} at or below reorder level</div>
    </div>
    <div class="stat-card">
        <div class="stat-top"><span>Staff</span><div class="stat-icon" aria-hidden="true">&#9786;</div></div>
        <div class="stat-number">{{ number_format($s['users']) }}</div>
        <div class="stat-caption">{{ \App\Support\Fmt::bytes($s['db_size']) }} database</div>
    </div>
</section>

<div class="cols">
    {{-- ================= LEFT COLUMN ================= --}}
    <div>
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Staff accounts</h2>
                    <p>Impersonate a staff member to preview their view</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <caption class="sr-only">Staff accounts for this company</caption>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="num"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>
                                <div class="cell-main">{{ $u->name }}</div>
                                <div class="cell-sub">{{ $u->email }}</div>
                            </td>
                            <td><span class="badge role">{{ ucfirst($u->role) }}</span></td>
                            <td>
                                <span class="badge {{ $u->active ? 'active' : 'suspended' }}">
                                    {{ $u->active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="num">
                                <form class="inline" method="POST"
                                      action="{{ route('super.companies.users.impersonate', [$tenant->id, $u->id]) }}">
                                    @csrf
                                    <button class="btn btn-small" type="submit"
                                            @disabled(! $u->active || $tenant->suspended)
                                            @if (! $u->active) title="Account disabled"
                                            @elseif ($tenant->suspended) title="Company suspended" @endif>
                                        Sign in as
                                    </button>
                                </form>
                                <form class="inline" method="POST"
                                      action="{{ route('super.companies.users.reset', [$tenant->id, $u->id]) }}"
                                      data-confirm="Generate a new password for {{ $u->email }}?">
                                    @csrf
                                    <button class="btn btn-small" type="submit">Reset password</button>
                                </form>
                                <form class="inline" method="POST"
                                      action="{{ route('super.companies.users.toggle', [$tenant->id, $u->id]) }}">
                                    @csrf
                                    <button class="btn btn-small {{ $u->active ? 'btn-warn' : '' }}" type="submit">
                                        {{ $u->active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <strong>No staff accounts</strong>
                                    <p>Add the first account below.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <details class="disclose"
                     @if ($errors->has('user_email') || $errors->has('user_name') || $errors->has('user_password')) open @endif>
                <summary>Add a staff account</summary>
                <form method="POST" action="{{ route('super.companies.users.store', $tenant->id) }}" class="panel-body">
                    @csrf
                    <div class="grid-2">
                        <div class="field">
                            <label for="user_name">Full name</label>
                            <input id="user_name" type="text" name="user_name" value="{{ old('user_name') }}" required>
                            @error('user_name')<small class="error">{{ $message }}</small>@enderror
                        </div>
                        <div class="field">
                            <label for="user_email">Email</label>
                            <input id="user_email" type="email" name="user_email" value="{{ old('user_email') }}" required>
                            @error('user_email')<small class="error">{{ $message }}</small>@enderror
                        </div>
                        <div class="field">
                            <label for="user_password">Password</label>
                            <input id="user_password" type="password" name="user_password"
                                   minlength="8" required autocomplete="new-password">
                            @error('user_password')<small class="error">{{ $message }}</small>@enderror
                        </div>
                        <div class="field">
                            <label for="user_role">Role</label>
                            <select id="user_role" name="user_role">
                                @foreach (['admin', 'manager', 'cashier'] as $r)
                                    <option value="{{ $r }}" @selected(old('user_role', 'cashier') === $r)>{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Create account</button>
                </form>
            </details>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Recent orders</h2>
                    <p>8 most recent</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <caption class="sr-only">Most recent orders for this company</caption>
                    <thead>
                        <tr><th scope="col">Receipt</th><th scope="col">Status</th><th scope="col" class="num">Total</th><th scope="col">When</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($orders as $o)
                        @php
                            $tone = $o->status === 'paid' ? 'active' : ($o->status === 'pending' ? 'info' : 'suspended');
                        @endphp
                        <tr>
                            <td class="mono">{{ $o->receipt_no }}</td>
                            <td><span class="badge {{ $tone }}">{{ ucfirst($o->status) }}</span></td>
                            <td class="num">{{ \App\Support\Fmt::money($o->total) }}</td>
                            <td class="cell-sub">
                                {{ $o->created_at ? \Carbon\Carbon::parse($o->created_at)->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <strong>No orders yet</strong>
                                    <p>Orders will appear here after the first sale.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Best sellers</h2>
                    <p>By sales value</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <caption class="sr-only">Best-selling products by sales value</caption>
                    <thead>
                        <tr><th scope="col">Product</th><th scope="col" class="num">Units</th><th scope="col" class="num">Sales</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($topProducts as $p)
                        <tr>
                            <td class="cell-main">{{ $p->product_name }}</td>
                            <td class="num">{{ number_format($p->qty) }}</td>
                            <td class="num">{{ \App\Support\Fmt::money($p->amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state compact"><strong>No sales recorded</strong></div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- ================= RIGHT COLUMN ================= --}}
    <div>
        <form class="panel" method="POST" action="{{ route('super.companies.update', $tenant->id) }}">
            @csrf
            @method('PATCH')
            <div class="panel-heading">
                <div>
                    <h2>Company details</h2>
                    <p>Only visible to platform admins</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="field">
                    <label for="name">Company name</label>
                    <input id="name" type="text" name="name"
                           value="{{ old('name', $tenant->name) }}"
                           class="{{ $errors->has('name') ? 'err' : '' }}" required>
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="plan">Plan</label>
                    <select id="plan" name="plan">
                        @foreach ($plans as $p)
                            <option value="{{ $p }}" @selected(old('plan', $tenant->plan ?: 'basic') === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                    @error('plan')<small class="error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="contact_email">Contact email</label>
                    <input id="contact_email" type="email" name="contact_email"
                           value="{{ old('contact_email', $tenant->contact_email) }}"
                           class="{{ $errors->has('contact_email') ? 'err' : '' }}">
                    @error('contact_email')<small class="error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="contact_phone">Contact phone</label>
                    <input id="contact_phone" type="text" name="contact_phone"
                           value="{{ old('contact_phone', $tenant->contact_phone) }}"
                           class="{{ $errors->has('contact_phone') ? 'err' : '' }}">
                    @error('contact_phone')<small class="error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="notes">Internal notes</label>
                    <textarea id="notes" name="notes">{{ old('notes', $tenant->notes) }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Save details</button>
            </div>
        </form>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Web addresses</h2>
                    <p>Domains where staff sign in</p>
                </div>
            </div>
            @foreach ($tenant->domains as $d)
                <div class="row">
                    <div class="grow">
                        <a class="text-link" href="{{ \App\Support\Fmt::url($d->domain) }}"
                           target="_blank" rel="noopener">{{ $d->domain }}</a>
                    </div>
                    @if ($tenant->domains->count() > 1)
                        <form class="inline" method="POST"
                              action="{{ route('super.companies.domains.destroy', [$tenant->id, $d->id]) }}"
                              data-confirm="Remove {{ $d->domain }}?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-small" type="submit">Remove</button>
                        </form>
                    @endif
                </div>
            @endforeach
            <form method="POST" action="{{ route('super.companies.domains.store', $tenant->id) }}" class="domain-add-form">
                @csrf
                <input type="text" name="domain" placeholder="shop.example.com"
                       aria-label="New domain" value="{{ old('domain') }}"
                       class="{{ $errors->has('domain') ? 'err' : '' }}" required>
                <button class="btn" type="submit">Add</button>
            </form>
            @error('domain')<div class="domain-error">{{ $message }}</div>@enderror
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>History</h2>
                    <p>Admin actions on this company</p>
                </div>
                <a class="text-link" href="{{ route('super.activity', ['company' => $tenant->id]) }}">All</a>
            </div>
            @forelse ($log as $l)
                <div class="row">
                    <div class="grow">
                        <strong>{{ ucfirst(str_replace(['.', '_'], ' ', $l->action)) }}</strong>
                        <div class="cell-sub">
                            {{ $l->super_admin_name }} &middot; {{ $l->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state compact"><strong>No history yet</strong></div>
            @endforelse
        </section>

        <section class="panel danger" id="danger">
            <div class="panel-heading">
                <div>
                    <h2>Danger zone</h2>
                    <p>These actions affect every staff member</p>
                </div>
            </div>
            @unless ($tenant->suspended)
                <form method="POST" action="{{ route('super.companies.suspend', $tenant->id) }}" class="panel-body">
                    @csrf
                    <div class="field">
                        <label for="reason">Suspend this company</label>
                        <input id="reason" type="text" name="reason" maxlength="255"
                               placeholder="Reason (shown to staff, optional)">
                        <small>Staff are blocked immediately. Data is kept and nothing is deleted.</small>
                    </div>
                    <button class="btn btn-warn" type="submit">Suspend company</button>
                </form>
            @endunless
            <form method="POST" action="{{ route('super.companies.destroy', $tenant->id) }}"
                  data-confirm="Permanently delete {{ $tenant->id }} and its database? This cannot be undone.">
                @csrf
                @method('DELETE')
                <div class="panel-body">
                    <div class="field">
                        <label for="confirm">Delete this company</label>
                        <input id="confirm" type="text" name="confirm"
                               placeholder="Type {{ $tenant->id }} to confirm" autocomplete="off"
                               class="{{ $errors->has('confirm') ? 'err' : '' }}">
                        @error('confirm')<small class="error">{{ $message }}</small>@enderror
                        <small>Deletes the company and its whole database. This cannot be undone.</small>
                    </div>
                    <button class="btn btn-danger" type="submit">Delete permanently</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    // Delegated confirmation for forms with data-confirm.
    // Avoids interpolating Blade values into inline JS strings.
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-confirm]');
        if (!form) return;
        if (!window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);
</script>
@endsection