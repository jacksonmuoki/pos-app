
@extends('super.layout')
@section('title', 'Companies')

@section('content')
@php
    $activeFilters = collect([
        'q'      => $q !== '' ? $q : null,
        'status' => $status !== 'all' ? $status : null,
        'sort'   => $sort !== 'newest' ? $sort : null,
    ])->filter();

    $hasFilters = $activeFilters->isNotEmpty();
@endphp

<style>
    .companies-page {
        font-family: 'Manrope', sans-serif;
        color: #1e293b;
    }

    .companies-page * {
        box-sizing: border-box;
    }

    .companies-page .page-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 20px;
    }

    .companies-page .page-heading h1 {
        margin: 0 0 5px;
        font-size: 21px;
        font-weight: 700;
        letter-spacing: -.5px;
        color: #1e293b;
    }

    .companies-page .page-heading p {
        margin: 0;
        color: #7b8a9c;
        font-size: 12.5px;
    }

    .companies-page .panel {
        background: #fff;
        border: 1px solid #e6eaef;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    .companies-page .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 36px;
        padding: 0 13px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
        transition: .18s ease;
    }

    .companies-page .btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .companies-page .btn-primary {
        background: #128d51;
        border-color: #128d51;
        color: #fff;
    }

    .companies-page .btn-primary:hover {
        background: #0f7545;
        border-color: #0f7545;
        color: #fff;
    }

    .companies-page .btn-small {
        min-height: 30px;
        padding: 0 11px;
        font-size: 11.5px;
    }

    .companies-page .filter-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 11px;
        padding: 15px 17px;
        border-bottom: 1px solid #e6eaef;
        background: #fff;
    }

    .companies-page .filter-search {
        position: relative;
        flex: 1 1 260px;
        min-width: 200px;
    }

    .companies-page .filter-search svg {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }

    .companies-page .filter-search input {
        width: 100%;
        height: 42px;
        padding: 0 12px 0 37px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #334155;
        font-family: inherit;
        font-size: 12px;
        outline: none;
        transition: .18s ease;
    }

    .companies-page input:focus,
    .companies-page select:focus {
        border-color: #128d51 !important;
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .09);
        outline: none;
    }

    .companies-page .filter-group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .companies-page .filter-group--end {
        margin-left: auto;
    }

    .companies-page .chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        min-height: 34px;
        padding: 0 12px;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #fff;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: .18s ease;
    }

    .companies-page .chip input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .companies-page .chip:hover {
        border-color: #b8d9c8;
        color: #128d51;
    }

    .companies-page .chip.is-active {
        background: #edf8f1;
        border-color: #b9dfc9;
        color: #128d51;
    }

    .companies-page .chip:focus-within {
        outline: 2px solid rgba(18, 141, 81, .25);
        outline-offset: 2px;
    }

    .companies-page .select-wrap select {
        height: 42px;
        min-width: 145px;
        padding: 0 30px 0 11px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #475569;
        font-family: inherit;
        font-size: 11.5px;
        cursor: pointer;
        outline: none;
    }

    .companies-page .active-filters {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        padding: 11px 17px;
        border-bottom: 1px solid #e6eaef;
        background: #f8fafc;
        color: #64748b;
        font-size: 11.5px;
    }

    .companies-page .active-filters-label {
        font-weight: 700;
    }

    .companies-page .pill {
        padding: 4px 9px;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #fff;
        color: #475569;
        font-size: 10.5px;
    }

    .companies-page .table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .companies-page table.companies {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .companies-page table.companies thead th {
        padding: 13px 15px;
        background: #f8fafc;
        border-bottom: 1px solid #e6eaef;
        color: #7b8a9c;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .055em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .companies-page table.companies thead th.num {
        text-align: right;
    }

    .companies-page table.companies tbody td {
        padding: 14px 15px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .companies-page table.companies tbody tr.company-row {
        cursor: pointer;
        transition: background .15s ease;
    }

    .companies-page table.companies tbody tr.company-row:hover {
        background: #f8fcf9;
    }

    .companies-page table.companies tbody tr.company-row:focus-visible {
        outline: 2px solid #128d51;
        outline-offset: -2px;
    }

    .companies-page table.companies tbody tr:last-child td {
        border-bottom: 0;
    }

    .companies-page td.num {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .companies-page .company-identity {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        min-width: 190px;
    }

    .companies-page .symbol-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 9px;
        background: #edf8f1;
        color: #128d51;
        font-size: 14px;
        font-weight: 800;
    }

    .companies-page .cell-main {
        display: inline-block;
        color: #1e293b;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .companies-page .cell-main:hover {
        color: #128d51;
    }

    .companies-page .cell-sub {
        margin-top: 3px;
        color: #94a3b8;
        font-size: 10.5px;
    }

    .companies-page .cell-sub.mono {
        font-family: Consolas, monospace;
        font-size: 10px;
    }

    .companies-page .cell-warn {
        color: #b7791f;
        font-weight: 600;
    }

    .companies-page .company-domains {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 9px;
        margin-top: 5px;
    }

    .companies-page .domain-link {
        font-size: 10px;
    }

    .companies-page .text-link {
        color: #128d51;
        text-decoration: none;
    }

    .companies-page .text-link:hover {
        text-decoration: underline;
    }

    .companies-page .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.4;
        white-space: nowrap;
    }

    .companies-page .badge.plan {
        background: #f1f5f9;
        color: #475569;
    }

    .companies-page .badge.active {
        background: #eaf7ef;
        color: #16804b;
    }

    .companies-page .badge.suspended {
        background: #fff0f0;
        color: #c24141;
    }

    .companies-page .badge.bad {
        background: #fff6e8;
        color: #a16207;
    }

    .companies-page .actions-col {
        width: 1%;
        white-space: nowrap;
    }

    .companies-page .actions-cell {
        text-align: right;
    }

    .companies-page .empty-state {
        padding: 45px 20px;
        text-align: center;
        color: #94a3b8;
    }

    .companies-page .empty-state strong {
        display: block;
        margin-bottom: 5px;
        color: #334155;
        font-size: 14px;
    }

    .companies-page .empty-state p {
        margin: 0;
        font-size: 12px;
    }

    .companies-page .empty-state-action {
        margin-top: 15px;
    }

    .companies-page .pagination-wrap {
        display: flex;
        justify-content: flex-end;
        padding: 14px 17px;
        border-top: 1px solid #e6eaef;
    }

    .companies-page .sr-only {
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

    @media (max-width: 900px) {
        .companies-page .filter-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .companies-page .filter-group--end {
            margin-left: 0;
            justify-content: flex-start;
        }

        .companies-page .filter-search {
            flex-basis: auto;
        }

        .companies-page table.companies thead {
            display: none;
        }

        .companies-page table.companies,
        .companies-page table.companies tbody {
            display: block;
            width: 100%;
        }

        .companies-page table.companies tbody tr.company-row {
            display: block;
            margin: 12px;
            padding: 12px 14px;
            width: calc(100% - 24px);
            border: 1px solid #e6eaef;
            border-radius: 10px;
            background: #fff;
        }

        .companies-page table.companies tbody tr.company-row td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 7px 0;
            border: 0;
            text-align: right;
        }

        .companies-page table.companies tbody tr.company-row td::before {
            content: attr(data-label);
            flex: 0 0 auto;
            color: #94a3b8;
            font-size: 10.5px;
            font-weight: 600;
            text-align: left;
        }

        .companies-page table.companies tbody tr.company-row td[data-label="Company"] {
            display: block;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }

        .companies-page table.companies tbody tr.company-row td[data-label="Company"]::before {
            display: none;
        }

        .companies-page table.companies tbody tr.company-row td[data-label="Company"] .company-identity {
            min-width: 0;
        }

        .companies-page table.companies tbody tr.company-row td.num {
            text-align: right;
        }

        .companies-page .actions-cell {
            justify-content: flex-end;
            padding-top: 10px !important;
        }

        .companies-page .actions-cell::before {
            display: none;
        }

        .companies-page .actions-cell .btn {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .companies-page .page-heading h1 {
            font-size: 19px;
        }

        .companies-page .page-heading > .btn {
            width: 100%;
        }

        .companies-page .filter-group--end {
            flex-wrap: wrap;
        }

        .companies-page .select-wrap {
            flex: 1;
        }

        .companies-page .select-wrap select {
            width: 100%;
        }
    }
</style>

<div class="companies-page">

    <header class="page-heading">
        <div>
            <h1>Companies</h1>
            <p>
                {{ $rows->count() }} of {{ $total }} shown.
                Open a company to manage its staff, domains and status.
            </p>
        </div>

        <a href="{{ route('super.companies.create') }}" class="btn btn-primary">
            <span aria-hidden="true">+</span>
            Register company
        </a>
    </header>

    <section class="panel">

        <form method="GET" class="filter-bar" role="search">

            <div class="filter-search">
                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill="currentColor" d="M11.7 10.3a6 6 0 1 1-1.4 1.4l3.5 3.5a1 1 0 0 0 1.4-1.4l-3.5-3.5ZM6.5 11a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z"/>
                </svg>

                <input
                    type="search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search by name, ID, domain or email"
                    aria-label="Search companies"
                    autocomplete="off"
                >
            </div>

            <div class="filter-group" role="group" aria-label="Status">
                @foreach (['all' => 'All', 'active' => 'Active', 'suspended' => 'Suspended'] as $val => $text)
                    <label class="chip {{ $status === $val ? 'is-active' : '' }}">
                        <input
                            type="radio"
                            name="status"
                            value="{{ $val }}"
                            @checked($status === $val)
                        >
                        <span>{{ $text }}</span>
                    </label>
                @endforeach
            </div>

            <div class="filter-group filter-group--end">
                <label class="select-wrap">
                    <span class="sr-only">Sort</span>

                    <select name="sort" aria-label="Sort">
                        <option value="newest" @selected($sort === 'newest')>Newest first</option>
                        <option value="name" @selected($sort === 'name')>Name</option>
                        <option value="revenue" @selected($sort === 'revenue')>Highest revenue</option>
                        <option value="orders" @selected($sort === 'orders')>Most orders</option>
                    </select>
                </label>

                <button class="btn" type="submit">Apply filters</button>

                @if ($hasFilters)
                    <a class="text-link" href="{{ route('super.companies.index') }}">Reset</a>
                @endif
            </div>

        </form>

        @if ($hasFilters)
            <div class="active-filters" aria-label="Active filters">
                <span class="active-filters-label">Filtered by:</span>

                @if ($q !== '')
                    <span class="pill">“{{ $q }}”</span>
                @endif

                @if ($status !== 'all')
                    <span class="pill">{{ ucfirst($status) }}</span>
                @endif

                @if ($sort !== 'newest')
                    <span class="pill">{{ ucfirst($sort) }}</span>
                @endif
            </div>
        @endif

        <div class="table-wrap">
            <table class="data companies">
                <caption class="sr-only">
                    Companies with plan, staff, products, orders, revenue and status
                </caption>

                <thead>
                    <tr>
                        <th scope="col">Company</th>
                        <th scope="col">Plan</th>
                        <th scope="col" class="num">Staff</th>
                        <th scope="col" class="num">Products</th>
                        <th scope="col" class="num">Orders</th>
                        <th scope="col" class="num">Revenue</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="actions-col">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rows as $r)
                        @php
                            $t = $r['tenant'];
                            $s = $r['stats'];
                            $href = route('super.companies.show', $t->id);
                        @endphp

                        <tr class="company-row" data-href="{{ $href }}">

                            <td data-label="Company">
                                <div class="company-identity">
                                    <div class="symbol-sm" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($t->name ?: $t->id, 0, 1)) }}
                                    </div>

                                    <div>
                                        <a class="cell-main" href="{{ $href }}">
                                            {{ $t->name ?: $t->id }}
                                        </a>

                                        <div class="cell-sub mono">{{ $t->id }}</div>

                                        @if ($t->domains->isNotEmpty())
                                            <div class="company-domains">
                                                @foreach ($t->domains as $d)
                                                    <a
                                                        href="{{ \App\Support\Fmt::url($d->domain) }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="text-link domain-link"
                                                    >
                                                        {{ $d->domain }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td data-label="Plan">
                                <span class="badge plan">
                                    {{ ucfirst($t->plan ?: 'basic') }}
                                </span>
                            </td>

                            <td data-label="Staff" class="num">
                                {{ number_format($s['users']) }}
                            </td>

                            <td data-label="Products" class="num">
                                {{ number_format($s['products']) }}

                                @if ($s['low_stock'])
                                    <div class="cell-sub cell-warn">
                                        {{ $s['low_stock'] }} low
                                    </div>
                                @endif
                            </td>

                            <td data-label="Orders" class="num">
                                {{ number_format($s['orders']) }}
                            </td>

                            <td data-label="Revenue" class="num">
                                <div class="cell-main">
                                    {{ \App\Support\Fmt::money($s['revenue']) }}
                                </div>

                                <div class="cell-sub">
                                    {{ \App\Support\Fmt::money($s['today']) }} today
                                </div>
                            </td>

                            <td data-label="Status">
                                @if (! $s['ok'])
                                    <span
                                        class="badge bad"
                                        title="The company database could not be read"
                                    >
                                        Database error
                                    </span>
                                @elseif ($t->suspended)
                                    <x-status-badge :active="false" />
                                @else
                                    <x-status-badge :active="true" />
                                @endif
                            </td>

                            <td class="actions-cell">
                                <a class="btn btn-small" href="{{ $href }}">
                                    Manage
                                </a>
                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty-state
                                    :title="$total ? 'No companies match these filters' : 'No companies yet'"
                                    :text="$total
                                        ? 'Try a different search or reset the filters.'
                                        : 'Register the first company to get started.'"
                                >
                                    @if ($total)
                                        <a class="btn" href="{{ route('super.companies.index') }}">
                                            Reset filters
                                        </a>
                                    @else
                                        <a
                                            class="btn btn-primary"
                                            href="{{ route('super.companies.create') }}"
                                        >
                                            Register company
                                        </a>
                                    @endif
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($rows, 'links') && $rows->hasPages())
            <div class="pagination-wrap">
                {{ $rows->withQueryString()->links() }}
            </div>
        @endif

    </section>
</div>

<script>
    document.querySelectorAll('.companies-page tr.company-row[data-href]').forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a, button, input, select, label')) return;
            window.location = row.dataset.href;
        });

        row.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target === row) {
                window.location = row.dataset.href;
            }
        });

        row.setAttribute('tabindex', '0');
    });
</script>
@endsection
