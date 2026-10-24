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
    /* ---- Page heading -------------------------------------------------- */
    .page-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .page-heading h1 {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 4px;
        letter-spacing: -0.01em;
    }
    .page-heading p.muted {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    /* ---- Filter bar ---------------------------------------------------- */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .filter-search {
        position: relative;
        flex: 1 1 260px;
        min-width: 220px;
    }
    .filter-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
    }
    .filter-search input {
        width: 100%;
        height: 38px;
        padding: 0 12px 0 34px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        font-size: 13px;
        color: inherit;
        transition: border-color .15s ease, box-shadow .15s ease;
        box-sizing: border-box;
    }
    .filter-search input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
    }

    /* Segmented status chips */
    .filter-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .filter-group--end { margin-left: auto; }

    .chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #fff;
        font-size: 12.5px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        user-select: none;
        transition: background .15s ease, border-color .15s ease, color .15s ease;
    }
    .chip input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .chip:hover {
        border-color: #cbd5e1;
        color: inherit;
    }
    .chip.is-active {
        background: rgba(37, 99, 235, .08);
        border-color: #2563eb;
        color: #2563eb;
    }
    .chip:focus-within {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    /* Sort select */
    .select-wrap { display: inline-flex; }
    .select-wrap select {
        height: 38px;
        padding: 0 32px 0 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background:
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8.5 1.5 4h9z'/%3E%3C/svg%3E")
            no-repeat right 10px center;
        appearance: none;
        -webkit-appearance: none;
        background-color: #fff;
        font-size: 12.5px;
        color: inherit;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .select-wrap select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
    }

    /* Active filter pills */
    .active-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 12px;
        color: #6b7280;
        background: #f8fafc;
    }
    .active-filters-label { font-weight: 500; }
    .pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #e5e7eb;
        color: inherit;
        font-size: 11.5px;
    }

    /* ---- Table --------------------------------------------------------- */
    table.companies {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    table.companies thead th {
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6b7280;
        padding: 12px 14px;
        background: #fafafa;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    table.companies thead th.num { text-align: right; }
    table.companies tbody td {
        padding: 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    table.companies tbody td.num {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    table.companies tbody tr.company-row {
        cursor: pointer;
        transition: background .12s ease;
    }
    table.companies tbody tr.company-row:hover { background: #f8fafc; }
    table.companies tbody tr.company-row:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: -2px;
    }
    table.companies tbody tr.company-row:last-child td { border-bottom: 0; }

    /* Company identity cell */
    .company-identity {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }
    .symbol-sm {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        background: rgba(37, 99, 235, .08);
        color: #2563eb;
        flex: 0 0 auto;
    }
    .cell-main {
        display: inline-block;
        font-weight: 500;
        color: #111827;
        text-decoration: none;
    }
    .cell-main:hover { color: #2563eb; }
    .cell-sub {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }
    .cell-sub.mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11px;
    }
    .cell-warn { color: #b45309; }

    .company-domains {
        display: flex;
        flex-wrap: wrap;
        gap: 2px 10px;
        margin-top: 4px;
    }
    .domain-link { font-size: 11.5px; }
    .text-link {
        color: #2563eb;
        text-decoration: none;
    }
    .text-link:hover { text-decoration: underline; }

    /* Badges */
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
    .badge.active    { background: #dcfce7; color: #166534; }
    .badge.suspended { background: #fee2e2; color: #991b1b; }
    .badge.bad       { background: #fef3c7; color: #92400e; }

    /* Buttons */
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
        transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        white-space: nowrap;
    }
    .btn:hover { background: #f9fafb; border-color: #cbd5e1; }
    .btn:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .btn-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }
    .btn-small {
        height: 30px;
        padding: 0 10px;
        font-size: 12px;
    }

    /* Actions column */
    .actions-col { width: 1%; white-space: nowrap; }
    .actions-cell { text-align: right; }

    /* Empty state */
    .empty-state {
        padding: 32px 20px;
        text-align: center;
        color: #6b7280;
    }
    .empty-state strong {
        display: block;
        color: #111827;
        font-size: 14px;
        margin-bottom: 4px;
    }
    .empty-state p { margin: 0; font-size: 13px; }
    .empty-state-action { margin-top: 14px; }

    /* Pagination */
    .pagination-wrap {
        padding: 14px 18px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
    }

    /* Utility */
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

    /* ---- Responsive: card layout under 900px -------------------------- */
    @media (max-width: 900px) {
        .filter-bar { flex-direction: column; align-items: stretch; }
        .filter-group--end { margin-left: 0; justify-content: space-between; }

        table.companies thead { display: none; }
        table.companies,
        table.companies tbody,
        table.companies tr,
        table.companies td {
            display: block;
            width: 100%;
        }
        table.companies tbody tr.company-row {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin: 12px;
            background: #fff;
        }
        table.companies tbody tr.company-row td {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 6px 0;
            border: 0;
            text-align: right;
        }
        table.companies tbody tr.company-row td::before {
            content: attr(data-label);
            color: #6b7280;
            font-weight: 500;
            text-align: left;
            flex: 0 0 auto;
        }
        table.companies tbody tr.company-row td[data-label="Company"] {
            display: block;
            text-align: left;
            padding-bottom: 10px;
        }
        table.companies tbody tr.company-row td[data-label="Company"]::before {
            display: none;
        }
        table.companies tbody tr.company-row td.num {
            text-align: right;
        }
        .actions-cell { justify-content: flex-end; }
        .actions-cell::before { content: none !important; }
        .actions-cell .btn { width: 100%; }
    }
</style>

<header class="page-heading">
    <div>
        <h1>Companies</h1>
        <p class="muted">
            {{ $rows->count() }} of {{ $total }} shown.
            Open a company to manage its staff, domains and status.
        </p>
    </div>
    <a href="{{ route('super.companies.create') }}" class="btn btn-primary">
        <span aria-hidden="true">+</span> Register company
    </a>
</header>

<section class="panel">
    <form method="GET" class="filter-bar" role="search">
        <div class="filter-search">
            <svg class="filter-search-icon" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
                <path fill="currentColor" d="M11.7 10.3a6 6 0 1 1-1.4 1.4l3.5 3.5a1 1 0 0 0 1.4-1.4l-3.5-3.5ZM6.5 11a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z"/>
            </svg>
            <input type="search" name="q" value="{{ $q }}"
                   placeholder="Search by name, id, domain or email"
                   aria-label="Search companies" autocomplete="off">
        </div>

        <div class="filter-group" role="group" aria-label="Status">
            @foreach (['all' => 'All', 'active' => 'Active', 'suspended' => 'Suspended'] as $val => $text)
                <label class="chip {{ $status === $val ? 'is-active' : '' }}">
                    <input type="radio" name="status" value="{{ $val }}" @checked($status === $val)>
                    <span>{{ $text }}</span>
                </label>
            @endforeach
        </div>

        <div class="filter-group filter-group--end">
            <label class="select-wrap">
                <span class="sr-only">Sort</span>
                <select name="sort" aria-label="Sort">
                    <option value="newest"  @selected($sort === 'newest')>Newest first</option>
                    <option value="name"    @selected($sort === 'name')>Name</option>
                    <option value="revenue" @selected($sort === 'revenue')>Highest revenue</option>
                    <option value="orders"  @selected($sort === 'orders')>Most orders</option>
                </select>
            </label>
            <button class="btn" type="submit">Apply</button>
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
            <caption class="sr-only">Companies with plan, staff, products, orders, revenue and status</caption>
            <thead>
                <tr>
                    <th scope="col">Company</th>
                    <th scope="col">Plan</th>
                    <th scope="col" class="num">Staff</th>
                    <th scope="col" class="num">Products</th>
                    <th scope="col" class="num">Orders</th>
                    <th scope="col" class="num">Revenue</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="actions-col"><span class="sr-only">Actions</span></th>
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
                            <div class="symbol symbol-sm" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($t->name ?: $t->id, 0, 1)) }}
                            </div>
                            <div>
                                <a class="cell-main" href="{{ $href }}">{{ $t->name ?: $t->id }}</a>
                                <div class="cell-sub mono">{{ $t->id }}</div>
                                @if ($t->domains->isNotEmpty())
                                    <div class="company-domains">
                                        @foreach ($t->domains as $d)
                                            <a href="{{ \App\Support\Fmt::url($d->domain) }}"
                                               target="_blank" rel="noopener"
                                               class="text-link domain-link">{{ $d->domain }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="Plan">
                        <span class="badge plan">{{ ucfirst($t->plan ?: 'basic') }}</span>
                    </td>
                    <td data-label="Staff" class="num">{{ number_format($s['users']) }}</td>
                    <td data-label="Products" class="num">
                        {{ number_format($s['products']) }}
                        @if ($s['low_stock'])
                            <div class="cell-sub cell-warn">{{ $s['low_stock'] }} low</div>
                        @endif
                    </td>
                    <td data-label="Orders" class="num">{{ number_format($s['orders']) }}</td>
                    <td data-label="Revenue" class="num">
                        <div class="cell-main">{{ \App\Support\Fmt::money($s['revenue']) }}</div>
                        <div class="cell-sub">{{ \App\Support\Fmt::money($s['today']) }} today</div>
                    </td>
                    <td data-label="Status">
                        @if (! $s['ok'])
                            <span class="badge bad" title="The company database could not be read">Database error</span>
                        @elseif ($t->suspended)
                            <x-status-badge :active="false" />
                        @else
                            <x-status-badge :active="true" />
                        @endif
                    </td>
                    <td class="actions-cell">
                        <a class="btn btn-small" href="{{ $href }}">Manage</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <x-empty-state
                            :title="$total ? 'No companies match these filters' : 'No companies yet'"
                            :text="$total
                                ? 'Try a different search or reset the filters.'
                                : 'Register the first company to get started.'">
                            @if ($total)
                                <a class="btn" href="{{ route('super.companies.index') }}">Reset filters</a>
                            @else
                                <a class="btn btn-primary" href="{{ route('super.companies.create') }}">Register company</a>
                            @endif
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($rows, 'links') && $rows->hasPages())
        <div class="pagination-wrap">{{ $rows->withQueryString()->links() }}</div>
    @endif
</section>

<script>
    // Whole-row click target, while preserving inner links/buttons.
    document.querySelectorAll('tr.company-row[data-href]').forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a, button, input, select, label')) return;
            window.location = row.dataset.href;
        });
        row.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') window.location = row.dataset.href;
        });
        row.setAttribute('tabindex', '0');
    });
</script>
@endsection