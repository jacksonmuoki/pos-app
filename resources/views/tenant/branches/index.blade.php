
@extends('layouts.app')

@section('content')

@php
    $totalBranches = $totalBranches ?? $branches->total();
    $activeBranches = $activeBranches ?? 0;
    $inactiveBranches = $inactiveBranches ?? 0;
    $mainBranch = $mainBranch ?? null;
@endphp

<style>
.branch-page {
    --br-bg: var(--background, #f8fafc);
    --br-card: var(--surface, #ffffff);
    --br-text: var(--text, #0f172a);
    --br-muted: var(--muted, #64748b);
    --br-border: var(--border, #e2e8f0);
    --br-primary: #15803d;
    --br-primary-dark: #166534;
    --br-soft: #dcfce7;
    color: var(--br-text);
    width: 100%;
    min-width: 0;
}

.branch-page * {
    box-sizing: border-box;
}

.branch-page .br-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.branch-page .br-title {
    font-size: clamp(23px, 3vw, 30px);
    font-weight: 800;
    letter-spacing: -1px;
    margin: 0;
}

.branch-page .br-subtitle {
    color: var(--br-muted);
    font-size: 13px;
    margin-top: 7px;
}

.branch-page .br-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid var(--br-border);
    cursor: pointer;
    transition: .2s ease;
}

.branch-page .br-btn-primary {
    background: var(--br-primary);
    color: white;
    border-color: var(--br-primary);
}

.branch-page .br-btn-primary:hover {
    background: var(--br-primary-dark);
}

.branch-page .br-btn-light {
    color: var(--br-text);
    background: var(--br-card);
}

.branch-page .br-btn-light:hover {
    border-color: var(--br-primary);
    color: var(--br-primary);
}

.branch-page .br-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 23px;
}

.branch-page .br-stat {
    background: var(--br-card);
    border: 1px solid var(--br-border);
    border-radius: 14px;
    padding: 19px;
    min-width: 0;
}

.branch-page .br-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.branch-page .br-stat-label {
    color: var(--br-muted);
    font-size: 12px;
    font-weight: 650;
}

.branch-page .br-stat-icon {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--br-soft);
    color: var(--br-primary-dark);
    font-size: 17px;
}

.branch-page .br-stat-value {
    font-size: 27px;
    font-weight: 800;
    margin-top: 12px;
    letter-spacing: -.7px;
}

.branch-page .br-stat-note {
    font-size: 11px;
    color: var(--br-muted);
    margin-top: 4px;
}

.branch-page .br-panel {
    background: var(--br-card);
    border: 1px solid var(--br-border);
    border-radius: 15px;
    overflow: hidden;
}

.branch-page .br-panel-head {
    padding: 19px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    border-bottom: 1px solid var(--br-border);
}

.branch-page .br-panel-title {
    font-size: 16px;
    font-weight: 800;
    margin: 0;
}

.branch-page .br-panel-description {
    font-size: 12px;
    color: var(--br-muted);
    margin-top: 4px;
}

.branch-page .br-filters {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
}

.branch-page .br-input,
.branch-page .br-select {
    min-height: 40px;
    border: 1px solid var(--br-border);
    background: var(--br-card);
    color: var(--br-text);
    border-radius: 9px;
    padding: 9px 12px;
    outline: none;
    font-size: 13px;
    min-width: 0;
}

.branch-page .br-input {
    width: min(250px, 100%);
}

.branch-page .br-input:focus,
.branch-page .br-select:focus {
    border-color: var(--br-primary);
    box-shadow: 0 0 0 3px rgba(21,128,61,.10);
}

.branch-page .br-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.branch-page .br-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}

.branch-page .br-table th {
    background: var(--br-bg);
    color: var(--br-muted);
    text-align: left;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding: 13px 17px;
    font-weight: 800;
    white-space: nowrap;
}

.branch-page .br-table td {
    padding: 15px 17px;
    border-top: 1px solid var(--br-border);
    font-size: 12px;
    vertical-align: middle;
}

.branch-page .br-table tr:hover td {
    background: rgba(21,128,61,.025);
}

.branch-page .br-branch-name {
    font-size: 13px;
    font-weight: 750;
}

.branch-page .br-branch-code {
    font-size: 11px;
    color: var(--br-muted);
    margin-top: 4px;
}

.branch-page .br-contact {
    color: var(--br-muted);
    line-height: 1.7;
}

.branch-page .br-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 30px;
    padding: 6px 10px;
    font-size: 10px;
    font-weight: 750;
    white-space: nowrap;
}

.branch-page .br-status.active {
    color: #166534;
    background: #dcfce7;
}

.branch-page .br-status.inactive {
    color: #92400e;
    background: #fef3c7;
}

.branch-page .br-main {
    display: inline-flex;
    padding: 4px 8px;
    border-radius: 6px;
    background: #ecfdf5;
    color: #047857;
    font-size: 10px;
    font-weight: 800;
    margin-top: 5px;
}

.branch-page .br-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

.branch-page .br-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 33px;
    height: 33px;
    border: 1px solid var(--br-border);
    background: var(--br-card);
    color: var(--br-muted);
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.branch-page .br-action:hover {
    color: var(--br-primary);
    border-color: var(--br-primary);
}

.branch-page .br-action.delete:hover {
    color: #dc2626;
    border-color: #dc2626;
}

.branch-page .br-empty {
    text-align: center;
    padding: 55px 20px;
}

.branch-page .br-empty-icon {
    font-size: 35px;
    margin-bottom: 12px;
}

.branch-page .br-empty h3 {
    margin: 0;
    font-size: 16px;
}

.branch-page .br-empty p {
    font-size: 12px;
    color: var(--br-muted);
    margin: 7px 0 18px;
}

.branch-page .br-pagination {
    padding: 15px 18px;
    border-top: 1px solid var(--br-border);
}

.branch-page .br-mobile-list {
    display: none;
}

.branch-page .br-mobile-card {
    padding: 16px;
    border-bottom: 1px solid var(--br-border);
}

.branch-page .br-mobile-card:last-child {
    border-bottom: 0;
}

.branch-page .br-mobile-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.branch-page .br-mobile-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
    margin-top: 16px;
}

.branch-page .br-mobile-label {
    font-size: 10px;
    color: var(--br-muted);
    margin-bottom: 4px;
}

.branch-page .br-mobile-value {
    font-size: 12px;
    font-weight: 650;
    overflow-wrap: anywhere;
}

.branch-page .br-mobile-actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.branch-page .br-mobile-actions .br-btn {
    flex: 1;
    min-height: 37px;
    font-size: 11px;
}

@media (max-width: 900px) {
    .branch-page .br-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .branch-page .br-header {
        align-items: stretch;
    }

    .branch-page .br-header > .br-btn {
        width: 100%;
    }

    .branch-page .br-panel-head {
        align-items: stretch;
    }

    .branch-page .br-filters {
        width: 100%;
    }

    .branch-page .br-input,
    .branch-page .br-select {
        flex: 1;
        width: 100%;
    }

    .branch-page .br-table-wrap {
        display: none;
    }

    .branch-page .br-mobile-list {
        display: block;
    }

    .branch-page .br-stats {
        gap: 10px;
    }

    .branch-page .br-stat {
        padding: 14px;
    }

    .branch-page .br-stat-value {
        font-size: 23px;
    }
}

@media (max-width: 380px) {
    .branch-page .br-mobile-details {
        grid-template-columns: 1fr;
    }
}

html.dark .branch-page,
body.dark .branch-page,
[data-theme="dark"] .branch-page {
    --br-bg: #0f172a;
    --br-card: #111c2e;
    --br-text: #e2e8f0;
    --br-muted: #94a3b8;
    --br-border: #29364a;
    --br-soft: #123b29;
}

html.dark .branch-page .br-main,
body.dark .branch-page .br-main,
[data-theme="dark"] .branch-page .br-main {
    background: #123b29;
    color: #86efac;
}

html.dark .branch-page .br-status.active,
body.dark .branch-page .br-status.active,
[data-theme="dark"] .branch-page .br-status.active {
    background: #123b29;
    color: #86efac;
}

html.dark .branch-page .br-status.inactive,
body.dark .branch-page .br-status.inactive,
[data-theme="dark"] .branch-page .br-status.inactive {
    background: #422006;
    color: #fcd34d;
}
</style>

<div class="branch-page">

    {{-- Page heading --}}
    <div class="br-header">
        <div>
            <h1 class="br-title">Company Branches</h1>
            <p class="br-subtitle">
                Manage your business locations, branch contacts and operating status.
            </p>
        </div>

        <a href="{{ route('branches.create') }}" class="br-btn br-btn-primary">
            <span>＋</span> Add New Branch
        </a>
    </div>

    {{-- Statistics --}}
    <div class="br-stats">

        <div class="br-stat">
            <div class="br-stat-top">
                <span class="br-stat-label">Total Branches</span>
                <span class="br-stat-icon">⌂</span>
            </div>
            <div class="br-stat-value">{{ number_format($totalBranches) }}</div>
            <div class="br-stat-note">Registered company locations</div>
        </div>

        <div class="br-stat">
            <div class="br-stat-top">
                <span class="br-stat-label">Active Branches</span>
                <span class="br-stat-icon">✓</span>
            </div>
            <div class="br-stat-value">{{ number_format($activeBranches) }}</div>
            <div class="br-stat-note">Currently operational</div>
        </div>

        <div class="br-stat">
            <div class="br-stat-top">
                <span class="br-stat-label">Inactive Branches</span>
                <span class="br-stat-icon">◷</span>
            </div>
            <div class="br-stat-value">{{ number_format($inactiveBranches) }}</div>
            <div class="br-stat-note">Currently deactivated</div>
        </div>

        <div class="br-stat">
            <div class="br-stat-top">
                <span class="br-stat-label">Main Branch</span>
                <span class="br-stat-icon">★</span>
            </div>
            <div class="br-stat-value" style="font-size:19px">
                {{ $mainBranch?->name ?? 'Not assigned' }}
            </div>
            <div class="br-stat-note">Primary company location</div>
        </div>

    </div>

    {{-- Branch listing --}}
    <div class="br-panel">

        <div class="br-panel-head">
            <div>
                <h2 class="br-panel-title">All Branches</h2>
                <p class="br-panel-description">
                    View and manage your registered business locations.
                </p>
            </div>

            <form method="GET" action="{{ route('branches.index') }}" class="br-filters">

                <input
                    type="search"
                    name="search"
                    class="br-input"
                    placeholder="Search branch..."
                    value="{{ request('search') }}"
                >

                <select name="status" class="br-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>
                        Active
                    </option>
                    <option value="inactive" @selected(request('status') === 'inactive')>
                        Inactive
                    </option>
                </select>

                <button type="submit" class="br-btn br-btn-light">
                    Search
                </button>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('branches.index') }}" class="br-btn br-btn-light">
                        Reset
                    </a>
                @endif

            </form>
        </div>

        {{-- Desktop table --}}
        <div class="br-table-wrap">
            <table class="br-table">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Contact details</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Branch type</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td>
                                <div class="br-branch-name">
                                    {{ $branch->name }}
                                </div>
                                <div class="br-branch-code">
                                    Code: {{ $branch->code }}
                                </div>
                            </td>

                            <td>
                                <div class="br-contact">
                                    {{ $branch->phone ?: 'No phone provided' }}
                                    <br>
                                    {{ $branch->email ?: 'No email provided' }}
                                </div>
                            </td>

                            <td>
                                <div>{{ $branch->city ?: '—' }}</div>
                                <div class="br-branch-code">
                                    {{ $branch->address ?: 'Address not provided' }}
                                </div>
                            </td>

                            <td>
                                @if($branch->is_active)
                                    <span class="br-status active">● Active</span>
                                @else
                                    <span class="br-status inactive">● Inactive</span>
                                @endif
                            </td>

                            <td>
                                @if($branch->is_main)
                                    <span class="br-main">★ Main branch</span>
                                @else
                                    <span style="color:var(--br-muted)">Standard branch</span>
                                @endif
                            </td>

                            <td>
                                <div class="br-actions">
                                    <a href="{{ route('branches.show', $branch) }}"
                                       class="br-action"
                                       title="View branch">⌕</a>

                                    <a href="{{ route('branches.edit', $branch) }}"
                                       class="br-action"
                                       title="Edit branch">✎</a>

                                    <form method="POST"
                                          action="{{ route('branches.destroy', $branch) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this branch?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="br-action delete"
                                                title="Delete branch">×</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="br-empty">
                                    <div class="br-empty-icon">⌂</div>
                                    <h3>No branches found</h3>
                                    <p>
                                        Add your first company branch to start managing multiple locations.
                                    </p>
                                    <a href="{{ route('branches.create') }}"
                                       class="br-btn br-btn-primary">
                                        ＋ Add First Branch
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile branch cards --}}
        <div class="br-mobile-list">

            @forelse($branches as $branch)

                <div class="br-mobile-card">

                    <div class="br-mobile-top">
                        <div>
                            <div class="br-branch-name">{{ $branch->name }}</div>
                            <div class="br-branch-code">Code: {{ $branch->code }}</div>

                            @if($branch->is_main)
                                <span class="br-main">★ Main branch</span>
                            @endif
                        </div>

                        @if($branch->is_active)
                            <span class="br-status active">● Active</span>
                        @else
                            <span class="br-status inactive">● Inactive</span>
                        @endif
                    </div>

                    <div class="br-mobile-details">
                        <div>
                            <div class="br-mobile-label">Phone</div>
                            <div class="br-mobile-value">
                                {{ $branch->phone ?: 'Not provided' }}
                            </div>
                        </div>

                        <div>
                            <div class="br-mobile-label">Email</div>
                            <div class="br-mobile-value">
                                {{ $branch->email ?: 'Not provided' }}
                            </div>
                        </div>

                        <div>
                            <div class="br-mobile-label">City</div>
                            <div class="br-mobile-value">
                                {{ $branch->city ?: 'Not provided' }}
                            </div>
                        </div>

                        <div>
                            <div class="br-mobile-label">Address</div>
                            <div class="br-mobile-value">
                                {{ $branch->address ?: 'Not provided' }}
                            </div>
                        </div>
                    </div>

                    <div class="br-mobile-actions">
                        <a href="{{ route('branches.show', $branch) }}"
                           class="br-btn br-btn-light">View</a>

                        <a href="{{ route('branches.edit', $branch) }}"
                           class="br-btn br-btn-light">Edit</a>

                        <form method="POST"
                              action="{{ route('branches.destroy', $branch) }}"
                              style="flex:1"
                              onsubmit="return confirm('Delete this branch?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="br-btn br-btn-light"
                                    style="width:100%;color:#dc2626">
                                Delete
                            </button>
                        </form>
                    </div>

                </div>

            @empty

                <div class="br-empty">
                    <div class="br-empty-icon">⌂</div>
                    <h3>No branches found</h3>
                    <p>Add your first company branch to get started.</p>
                    <a href="{{ route('branches.create') }}"
                       class="br-btn br-btn-primary">
                        ＋ Add First Branch
                    </a>
                </div>

            @endforelse

        </div>

        {{-- Pagination --}}
        @if($branches->hasPages())
            <div class="br-pagination">
                {{ $branches->withQueryString()->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
