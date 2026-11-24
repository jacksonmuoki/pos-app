
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
    .company-details-page {
        font-family: 'Manrope', sans-serif;
        color: #1e293b;
    }

    .company-details-page * {
        box-sizing: border-box;
    }

    .company-details-page .crumb {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 16px;
        color: #7b8a9c;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none;
    }

    .company-details-page .crumb:hover {
        color: #128d51;
    }

    .company-details-page .page-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 20px;
    }

    .company-details-page .heading-identity {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .company-details-page .symbol-lg {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 11px;
        background: #edf8f1;
        color: #128d51;
        font-size: 19px;
        font-weight: 800;
    }

    .company-details-page .page-heading h1 {
        margin: 0 0 6px;
        color: #1e293b;
        font-size: 21px;
        font-weight: 700;
        letter-spacing: -.5px;
        line-height: 1.25;
    }

    .company-details-page .meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        margin: 0;
        color: #7b8a9c;
        font-size: 11px;
    }

    .company-details-page .meta .dot {
        color: #cbd5e1;
    }

    .company-details-page .meta .mono {
        font-family: Consolas, monospace;
        font-size: 10.5px;
    }

    .company-details-page .actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
    }

    .company-details-page .inline {
        display: inline-flex;
        margin: 0;
    }

    .company-details-page .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 36px;
        padding: 0 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-family: inherit;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
        transition: .18s ease;
    }

    .company-details-page .btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .company-details-page .btn:focus-visible {
        outline: 2px solid #128d51;
        outline-offset: 2px;
    }

    .company-details-page .btn-primary {
        background: #128d51;
        border-color: #128d51;
        color: #fff;
    }

    .company-details-page .btn-primary:hover {
        background: #0f7545;
        border-color: #0f7545;
        color: #fff;
    }

    .company-details-page .btn-warn {
        background: #fff;
        border-color: #f2d49a;
        color: #a16207;
    }

    .company-details-page .btn-warn:hover {
        background: #fffbeb;
        border-color: #eabf6b;
    }

    .company-details-page .btn-danger {
        background: #dc4b4b;
        border-color: #dc4b4b;
        color: #fff;
    }

    .company-details-page .btn-danger:hover {
        background: #bd3535;
        border-color: #bd3535;
    }

    .company-details-page .btn-small {
        min-height: 29px;
        padding: 0 9px;
        font-size: 10.5px;
    }

    .company-details-page .btn:disabled,
    .company-details-page .btn[disabled] {
        opacity: .45;
        cursor: not-allowed;
        background: #f8fafc;
    }

    .company-details-page .alert {
        padding: 12px 14px;
        margin-bottom: 15px;
        border-radius: 9px;
        font-size: 11.5px;
        line-height: 1.6;
    }

    .company-details-page .alert.warn {
        border: 1px solid #f3d99f;
        background: #fffbeb;
        color: #92400e;
    }

    .company-details-page .alert.bad {
        border: 1px solid #fecaca;
        background: #fff1f1;
        color: #b42318;
    }

    .company-details-page .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .company-details-page .stat-card {
        min-width: 0;
        padding: 15px;
        border: 1px solid #e6eaef;
        border-radius: 11px;
        background: #fff;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .02);
    }

    .company-details-page .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
        color: #7b8a9c;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .055em;
        text-transform: uppercase;
    }

    .company-details-page .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 13px;
    }

    .company-details-page .stat-icon.green {
        background: #eaf7ef;
        color: #128d51;
    }

    .company-details-page .stat-icon.blue {
        background: #eff6ff;
        color: #2563eb;
    }

    .company-details-page .stat-icon.amber {
        background: #fff7e8;
        color: #b7791f;
    }

    .company-details-page .stat-number {
        color: #1e293b;
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.6px;
        line-height: 1.25;
        font-variant-numeric: tabular-nums;
        overflow-wrap: anywhere;
    }

    .company-details-page .stat-number.sm {
        font-size: 17px;
        letter-spacing: -.3px;
    }

    .company-details-page .stat-caption {
        margin-top: 5px;
        color: #94a3b8;
        font-size: 10.5px;
    }

    .company-details-page .cols {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 15px;
        align-items: start;
    }

    .company-details-page .cols > div > * + * {
        margin-top: 15px;
    }

    .company-details-page .panel {
        min-width: 0;
        overflow: hidden;
        border: 1px solid #e6eaef;
        border-radius: 11px;
        background: #fff;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .02);
    }

    .company-details-page .panel.danger {
        border-color: #f0caca;
    }

    .company-details-page .panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #e6eaef;
    }

    .company-details-page .panel-heading h2,
    .company-details-page .panel-heading h3 {
        margin: 0;
        color: #1e293b;
        font-size: 12.5px;
        font-weight: 800;
    }

    .company-details-page .panel-heading p {
        margin: 4px 0 0;
        color: #94a3b8;
        font-size: 10.5px;
    }

    .company-details-page .panel-body {
        padding: 15px 16px;
    }

    .company-details-page .panel-body + .panel-body {
        border-top: 1px solid #e6eaef;
    }

    .company-details-page .table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .company-details-page table.data {
        width: 100%;
        border-collapse: collapse;
        font-size: 11.5px;
    }

    .company-details-page table.data thead th {
        padding: 11px 13px;
        border-bottom: 1px solid #e6eaef;
        background: #f8fafc;
        color: #7b8a9c;
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: .055em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .company-details-page table.data thead th.num {
        text-align: right;
    }

    .company-details-page table.data tbody td {
        padding: 12px 13px;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
        vertical-align: middle;
    }

    .company-details-page table.data tbody tr:last-child td {
        border-bottom: 0;
    }

    .company-details-page table.data tbody td.num {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .company-details-page .cell-main {
        color: #334155;
        font-size: 11.5px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .company-details-page .cell-sub {
        margin-top: 3px;
        color: #94a3b8;
        font-size: 10px;
        overflow-wrap: anywhere;
    }

    .company-details-page .cell-sub.mono,
    .company-details-page td.mono {
        font-family: Consolas, monospace;
        font-size: 10px;
    }

    .company-details-page .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 9px;
        border-radius: 20px;
        font-size: 9.5px;
        font-weight: 700;
        line-height: 1.4;
        white-space: nowrap;
    }

    .company-details-page .badge.plan {
        background: #edf8f1;
        color: #128d51;
    }

    .company-details-page .badge.role {
        background: #f1f5f9;
        color: #475569;
    }

    .company-details-page .badge.active {
        background: #eaf7ef;
        color: #16804b;
    }

    .company-details-page .badge.suspended {
        background: #fff0f0;
        color: #bd3535;
    }

    .company-details-page .badge.info {
        background: #eff6ff;
        color: #2563eb;
    }

    .company-details-page .badge.bad {
        background: #fff6e8;
        color: #a16207;
    }

    .company-details-page .field {
        margin-bottom: 13px;
    }

    .company-details-page .field:last-of-type {
        margin-bottom: 16px;
    }

    .company-details-page .field label {
        display: block;
        margin-bottom: 6px;
        color: #475569;
        font-size: 10.5px;
        font-weight: 700;
    }

    .company-details-page .field input[type="text"],
    .company-details-page .field input[type="email"],
    .company-details-page .field input[type="password"],
    .company-details-page .field select,
    .company-details-page .field textarea,
    .company-details-page .domain-add-form input {
        display: block;
        width: 100%;
        min-width: 0;
        height: 41px;
        padding: 0 11px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-family: inherit;
        font-size: 11.5px;
        outline: none;
        transition: .18s ease;
    }

    .company-details-page .field textarea {
        min-height: 85px;
        height: auto;
        padding: 10px 11px;
        line-height: 1.5;
        resize: vertical;
    }

    .company-details-page .field input:focus,
    .company-details-page .field select:focus,
    .company-details-page .field textarea:focus,
    .company-details-page .domain-add-form input:focus {
        border-color: #128d51;
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .09);
    }

    .company-details-page .field input.err,
    .company-details-page .field select.err,
    .company-details-page .field textarea.err,
    .company-details-page .domain-add-form input.err {
        border-color: #dc4b4b;
    }

    .company-details-page .field input.err:focus,
    .company-details-page .field select.err:focus,
    .company-details-page .field textarea.err:focus,
    .company-details-page .domain-add-form input.err:focus {
        box-shadow: 0 0 0 3px rgba(220, 75, 75, .1);
    }

    .company-details-page .field small {
        display: block;
        margin-top: 5px;
        color: #94a3b8;
        font-size: 10px;
        line-height: 1.5;
    }

    .company-details-page .field small.error,
    .company-details-page .domain-error {
        color: #c24141;
        font-weight: 600;
    }

    .company-details-page .grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 12px;
    }

    .company-details-page details.disclose {
        border-top: 1px solid #e6eaef;
    }

    .company-details-page details.disclose > summary {
        padding: 13px 16px;
        color: #128d51;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        list-style: none;
        user-select: none;
    }

    .company-details-page details.disclose > summary::-webkit-details-marker {
        display: none;
    }

    .company-details-page details.disclose > summary::before {
        content: "+";
        display: inline-block;
        width: 16px;
        font-size: 14px;
    }

    .company-details-page details.disclose[open] > summary::before {
        content: "−";
    }

    .company-details-page .row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 11.5px;
    }

    .company-details-page .row:last-of-type {
        border-bottom: 0;
    }

    .company-details-page .row .grow {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .company-details-page .row .grow strong {
        color: #334155;
        font-weight: 700;
    }

    .company-details-page .row .cell-sub {
        margin-top: 4px;
    }

    .company-details-page .domain-add-form {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 13px 15px;
        border-top: 1px solid #e6eaef;
    }

    .company-details-page .domain-add-form input {
        flex: 1 1 auto;
    }

    .company-details-page .domain-error {
        padding: 0 15px 12px;
        font-size: 10.5px;
    }

    .company-details-page .text-link {
        color: #128d51;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
    }

    .company-details-page .text-link:hover {
        text-decoration: underline;
    }

    .company-details-page .empty-state {
        padding: 27px 16px;
        color: #94a3b8;
        font-size: 11px;
        text-align: center;
    }

    .company-details-page .empty-state strong {
        display: block;
        margin-bottom: 5px;
        color: #334155;
        font-size: 12px;
    }

    .company-details-page .empty-state p {
        margin: 0;
    }

    .company-details-page .empty-state.compact {
        padding: 20px 14px;
    }

    .company-details-page .danger .panel-heading {
        background: #fffafa;
        border-bottom-color: #f5dddd;
    }

    .company-details-page .danger .panel-heading h2 {
        color: #b42318;
    }

    .company-details-page .danger .panel-body + form,
    .company-details-page .danger form + form {
        border-top: 1px solid #f5dddd;
    }

    .company-details-page .sr-only {
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

    @media (max-width: 1050px) {
        .company-details-page .cols {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .company-details-page .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .company-details-page .page-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .company-details-page .actions {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .company-details-page .stat-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .company-details-page .stat-card {
            padding: 12px;
        }

        .company-details-page .stat-number {
            font-size: 19px;
        }

        .company-details-page .stat-number.sm {
            font-size: 14px;
        }

        .company-details-page .grid-2 {
            grid-template-columns: 1fr;
        }

        .company-details-page .actions {
            flex-wrap: wrap;
        }

        .company-details-page .actions .btn,
        .company-details-page .actions form {
            flex: 1 1 auto;
        }

        .company-details-page .actions form .btn {
            width: 100%;
        }

        .company-details-page .panel-heading,
        .company-details-page .panel-body {
            padding-left: 13px;
            padding-right: 13px;
        }

        .company-details-page table.data {
            min-width: 480px;
        }

        .company-details-page .domain-add-form {
            padding-left: 12px;
            padding-right: 12px;
        }
    }
</style>

<div class="company-details-page">

    <a class="crumb" href="{{ route('super.companies.index') }}">
        &larr; Companies
    </a>

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

                    <span>
                        Created {{ $tenant->created_at?->format('d M Y') }}
                    </span>
                </p>
            </div>
        </div>

        <div class="actions">
            @if ($first)
                <a
                    class="btn"
                    href="{{ \App\Support\Fmt::url($first->domain) }}"
                    target="_blank"
                    rel="noopener"
                >
                    Open site
                </a>
            @endif

            <form class="inline" method="POST"
                  action="{{ route('super.companies.migrate', $tenant->id) }}">
                @csrf
                <button class="btn" type="submit">Run migrations</button>
            </form>

            @if ($tenant->suspended)
                <form class="inline" method="POST"
                      action="{{ route('super.companies.activate', $tenant->id) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Reactivate</button>
                </form>
            @endif
        </div>
    </header>

    @if ($tenant->suspended)
        <div class="alert warn" role="status">
            Suspended
            {{ $tenant->suspended_at ? \Carbon\Carbon::parse($tenant->suspended_at)->diffForHumans() : '' }}.

            @if ($tenant->suspended_reason)
                Reason: {{ $tenant->suspended_reason }}
            @endif

            Staff cannot sign in until you reactivate this company.
        </div>
    @endif

    @unless ($s['ok'])
        <div class="alert bad" role="alert">
            This company's database could not be read:
            {{ $s['error'] }}. Try "Run migrations".
        </div>
    @endunless

    <section class="stat-grid" aria-label="Company statistics">

        <div class="stat-card">
            <div class="stat-top">
                <span>Revenue</span>
                <div class="stat-icon green" aria-hidden="true">&#164;</div>
            </div>

            <div class="stat-number sm">
                {{ \App\Support\Fmt::money($s['revenue']) }}
            </div>

            <div class="stat-caption">
                {{ \App\Support\Fmt::money($s['today']) }} today
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Orders</span>
                <div class="stat-icon blue" aria-hidden="true">&#9776;</div>
            </div>

            <div class="stat-number">
                {{ number_format($s['orders']) }}
            </div>

            <div class="stat-caption">
                Last: {{ \App\Support\Fmt::ago($s['last_order']) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Products</span>
                <div class="stat-icon amber" aria-hidden="true">&#9635;</div>
            </div>

            <div class="stat-number">
                {{ number_format($s['products']) }}
            </div>

            <div class="stat-caption">
                {{ $s['low_stock'] }} at or below reorder level
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Staff</span>
                <div class="stat-icon" aria-hidden="true">&#9786;</div>
            </div>

            <div class="stat-number">
                {{ number_format($s['users']) }}
            </div>

            <div class="stat-caption">
                {{ \App\Support\Fmt::bytes($s['db_size']) }} database
            </div>
        </div>

    </section>

    <div class="cols">

        {{-- LEFT COLUMN --}}
        <div>

            {{-- STAFF ACCOUNTS --}}
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Staff accounts</h2>
                        <p>Manage access and staff accounts</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="data">
                        <caption class="sr-only">
                            Staff accounts for this company
                        </caption>

                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Role</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="num">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td>
                                        <div class="cell-main">{{ $u->name }}</div>
                                        <div class="cell-sub">{{ $u->email }}</div>
                                    </td>

                                    <td>
                                        <span class="badge role">
                                            {{ ucfirst($u->role) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge {{ $u->active ? 'active' : 'suspended' }}">
                                            {{ $u->active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </td>

                                    <td class="num">
                                        <form class="inline" method="POST"
                                              action="{{ route('super.companies.users.impersonate', [$tenant->id, $u->id]) }}">
                                            @csrf

                                            <button
                                                class="btn btn-small"
                                                type="submit"
                                                @disabled(! $u->active || $tenant->suspended)
                                                @if (! $u->active)
                                                    title="Account disabled"
                                                @elseif ($tenant->suspended)
                                                    title="Company suspended"
                                                @endif
                                            >
                                                Sign in as
                                            </button>
                                        </form>

                                        <form class="inline" method="POST"
                                              action="{{ route('super.companies.users.reset', [$tenant->id, $u->id]) }}"
                                              data-confirm="Generate a new password for {{ $u->email }}?">
                                            @csrf
                                            <button class="btn btn-small" type="submit">
                                                Reset password
                                            </button>
                                        </form>

                                        <form class="inline" method="POST"
                                              action="{{ route('super.companies.users.toggle', [$tenant->id, $u->id]) }}">
                                            @csrf
                                            <button
                                                class="btn btn-small {{ $u->active ? 'btn-warn' : '' }}"
                                                type="submit"
                                            >
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

                <details
                    class="disclose"
                    @if ($errors->has('user_email') || $errors->has('user_name') || $errors->has('user_password'))
                        open
                    @endif
                >
                    <summary>Add a staff account</summary>

                    <form method="POST"
                          action="{{ route('super.companies.users.store', $tenant->id) }}"
                          class="panel-body">
                        @csrf

                        <div class="grid-2">
                            <div class="field">
                                <label for="user_name">Full name</label>
                                <input
                                    id="user_name"
                                    type="text"
                                    name="user_name"
                                    value="{{ old('user_name') }}"
                                    required
                                >
                                @error('user_name')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="user_email">Email</label>
                                <input
                                    id="user_email"
                                    type="email"
                                    name="user_email"
                                    value="{{ old('user_email') }}"
                                    required
                                >
                                @error('user_email')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="user_password">Password</label>
                                <input
                                    id="user_password"
                                    type="password"
                                    name="user_password"
                                    minlength="8"
                                    required
                                    autocomplete="new-password"
                                >
                                @error('user_password')
                                    <small class="error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="user_role">Role</label>
                                <select id="user_role" name="user_role">
                                    @foreach (['admin', 'manager', 'cashier'] as $r)
                                        <option
                                            value="{{ $r }}"
                                            @selected(old('user_role', 'cashier') === $r)
                                        >
                                            {{ ucfirst($r) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">
                            Create account
                        </button>
                    </form>
                </details>
            </section>

            {{-- RECENT ORDERS --}}
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Recent orders</h2>
                        <p>8 most recent</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="data">
                        <caption class="sr-only">
                            Most recent orders for this company
                        </caption>

                        <thead>
                            <tr>
                                <th scope="col">Receipt</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="num">Total</th>
                                <th scope="col">When</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($orders as $o)
                                @php
                                    $tone = $o->status === 'paid'
                                        ? 'active'
                                        : ($o->status === 'pending' ? 'info' : 'suspended');
                                @endphp

                                <tr>
                                    <td class="mono">{{ $o->receipt_no }}</td>

                                    <td>
                                        <span class="badge {{ $tone }}">
                                            {{ ucfirst($o->status) }}
                                        </span>
                                    </td>

                                    <td class="num">
                                        {{ \App\Support\Fmt::money($o->total) }}
                                    </td>

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

            {{-- BEST SELLERS --}}
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Best sellers</h2>
                        <p>Products ranked by sales value</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="data">
                        <caption class="sr-only">
                            Best-selling products by sales value
                        </caption>

                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="num">Units</th>
                                <th scope="col" class="num">Sales</th>
                            </tr>
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
                                        <div class="empty-state compact">
                                            <strong>No sales recorded</strong>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        {{-- RIGHT COLUMN --}}
        <div>

            {{-- COMPANY DETAILS --}}
            <form class="panel"
                  method="POST"
                  action="{{ route('super.companies.update', $tenant->id) }}">
                @csrf
                @method('PATCH')

                <div class="panel-heading">
                    <div>
                        <h2>Company details</h2>
                        <p>Manage company information and subscription</p>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="field">
                        <label for="name">Company name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $tenant->name) }}"
                            class="{{ $errors->has('name') ? 'err' : '' }}"
                            required
                        >
                        @error('name')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="plan">Plan</label>
                        <select id="plan" name="plan">
                            @foreach ($plans as $p)
                                <option
                                    value="{{ $p }}"
                                    @selected(old('plan', $tenant->plan ?: 'basic') === $p)
                                >
                                    {{ ucfirst($p) }}
                                </option>
                            @endforeach
                        </select>
                        @error('plan')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="contact_email">Contact email</label>
                        <input
                            id="contact_email"
                            type="email"
                            name="contact_email"
                            value="{{ old('contact_email', $tenant->contact_email) }}"
                            class="{{ $errors->has('contact_email') ? 'err' : '' }}"
                        >
                        @error('contact_email')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="contact_phone">Contact phone</label>
                        <input
                            id="contact_phone"
                            type="text"
                            name="contact_phone"
                            value="{{ old('contact_phone', $tenant->contact_phone) }}"
                            class="{{ $errors->has('contact_phone') ? 'err' : '' }}"
                        >
                        @error('contact_phone')
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="notes">Internal notes</label>
                        <textarea id="notes" name="notes">{{ old('notes', $tenant->notes) }}</textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">
                        Save details
                    </button>
                </div>
            </form>

            {{-- WEB ADDRESSES --}}
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
                            <a
                                class="text-link"
                                href="{{ \App\Support\Fmt::url($d->domain) }}"
                                target="_blank"
                                rel="noopener"
                            >
                                {{ $d->domain }}
                            </a>
                        </div>

                        @if ($tenant->domains->count() > 1)
                            <form
                                class="inline"
                                method="POST"
                                action="{{ route('super.companies.domains.destroy', [$tenant->id, $d->id]) }}"
                                data-confirm="Remove {{ $d->domain }}?"
                            >
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-small" type="submit">
                                    Remove
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach

                <form
                    method="POST"
                    action="{{ route('super.companies.domains.store', $tenant->id) }}"
                    class="domain-add-form"
                >
                    @csrf

                    <input
                        type="text"
                        name="domain"
                        placeholder="shop.example.com"
                        aria-label="New domain"
                        value="{{ old('domain') }}"
                        class="{{ $errors->has('domain') ? 'err' : '' }}"
                        required
                    >

                    <button class="btn" type="submit">Add</button>
                </form>

                @error('domain')
                    <div class="domain-error">{{ $message }}</div>
                @enderror
            </section>

            {{-- HISTORY --}}
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>History</h2>
                        <p>Admin actions on this company</p>
                    </div>

                    <a
                        class="text-link"
                        href="{{ route('super.activity', ['company' => $tenant->id]) }}"
                    >
                        View all
                    </a>
                </div>

                @forelse ($log as $l)
                    <div class="row">
                        <div class="grow">
                            <strong>
                                {{ ucfirst(str_replace(['.', '_'], ' ', $l->action)) }}
                            </strong>

                            <div class="cell-sub">
                                {{ $l->super_admin_name }}
                                &middot;
                                {{ $l->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state compact">
                        <strong>No history yet</strong>
                    </div>
                @endforelse
            </section>

            {{-- DANGER ZONE --}}
            <section class="panel danger" id="danger">
                <div class="panel-heading">
                    <div>
                        <h2>Danger zone</h2>
                        <p>Actions that affect every staff member</p>
                    </div>
                </div>

                @unless ($tenant->suspended)
                    <form
                        method="POST"
                        action="{{ route('super.companies.suspend', $tenant->id) }}"
                        class="panel-body"
                    >
                        @csrf

                        <div class="field">
                            <label for="reason">Suspend this company</label>

                            <input
                                id="reason"
                                type="text"
                                name="reason"
                                maxlength="255"
                                placeholder="Reason (shown to staff, optional)"
                            >

                            <small>
                                Staff are blocked immediately. Data is kept and nothing is deleted.
                            </small>
                        </div>

                        <button class="btn btn-warn" type="submit">
                            Suspend company
                        </button>
                    </form>
                @endunless

                <form
                    method="POST"
                    action="{{ route('super.companies.destroy', $tenant->id) }}"
                    data-confirm="Permanently delete {{ $tenant->id }} and its database? This cannot be undone."
                >
                    @csrf
                    @method('DELETE')

                    <div class="panel-body">
                        <div class="field">
                            <label for="confirm">Delete this company</label>

                            <input
                                id="confirm"
                                type="text"
                                name="confirm"
                                placeholder="Type {{ $tenant->id }} to confirm"
                                autocomplete="off"
                                class="{{ $errors->has('confirm') ? 'err' : '' }}"
                            >

                            @error('confirm')
                                <small class="error">{{ $message }}</small>
                            @enderror

                            <small>
                                Deletes the company and its whole database. This cannot be undone.
                            </small>
                        </div>

                        <button class="btn btn-danger" type="submit">
                            Delete permanently
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </div>

</div>

<script>
    // Delegated confirmation for forms with data-confirm.
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
