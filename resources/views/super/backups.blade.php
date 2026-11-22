
@extends('super.layout')

@section('content')

<style>
    .backups-page {
        --bk-primary: #128d51;
        --bk-primary-dark: #0f7545;
        --bk-text: #1e293b;
        --bk-muted: #7b8a9c;
        --bk-border: #e6eaef;
        --bk-bg: #f8fafc;
        font-family: 'Manrope', sans-serif;
        color: var(--bk-text);
    }

    .backups-page * {
        box-sizing: border-box;
    }

    .backups-page .bk-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 23px;
    }

    .backups-page .bk-header h1 {
        font-size: 23px;
        font-weight: 800;
        letter-spacing: -.7px;
        margin: 0 0 6px;
    }

    .backups-page .bk-header p {
        font-size: 12px;
        color: var(--bk-muted);
        margin: 0;
    }

    .backups-page .bk-schedule {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        background: #effaf3;
        border: 1px solid #d7f0df;
        color: var(--bk-primary);
        border-radius: 8px;
        font-size: 11px;
        font-weight: 800;
    }

    .backups-page .bk-section {
        background: #fff;
        border: 1px solid var(--bk-border);
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 21px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
    }

    .backups-page .bk-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding-bottom: 15px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--bk-border);
    }

    .backups-page .bk-section-header h2 {
        font-size: 14px;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .backups-page .bk-section-header h2::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 5px;
        background: var(--bk-primary);
    }

    .backups-page .bk-section-header span {
        color: var(--bk-muted);
        font-size: 11px;
    }

    .backups-page .bk-form-row {
        display: flex;
        align-items: flex-end;
        gap: 13px;
        flex-wrap: wrap;
    }

    .backups-page .bk-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
        min-width: 200px;
    }

    .backups-page .bk-field label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
    }

    .backups-page .bk-field select {
        width: 100%;
        min-height: 42px;
        padding: 10px 35px 10px 12px;
        border: 1px solid var(--bk-border);
        border-radius: 8px;
        background-color: #fff;
        color: var(--bk-text);
        font-family: inherit;
        font-size: 12px;
        outline: none;
        cursor: pointer;
        transition: border-color .2s, box-shadow .2s;
    }

    .backups-page .bk-field select:focus {
        border-color: var(--bk-primary);
        box-shadow: 0 0 0 3px rgba(18, 141, 81, .09);
    }

    .backups-page .bk-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 7px;
        min-height: 37px;
        padding: 9px 14px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
        transition: background .2s, border-color .2s, transform .2s;
    }

    .backups-page .bk-btn-primary {
        background: var(--bk-primary);
        color: #fff;
    }

    .backups-page .bk-btn-primary:hover {
        background: var(--bk-primary-dark);
        transform: translateY(-1px);
    }

    .backups-page .bk-btn-light {
        background: #f1f5f9;
        color: #475569;
        border-color: var(--bk-border);
    }

    .backups-page .bk-btn-light:hover {
        background: #e8eef4;
    }

    .backups-page .bk-btn-danger {
        background: #fff1f2;
        color: #dc2626;
        border-color: #ffe0e3;
    }

    .backups-page .bk-btn-danger:hover {
        background: #ffe4e6;
    }

    .backups-page .bk-filter {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .backups-page .bk-filter .bk-field {
        max-width: 320px;
    }

    .backups-page .bk-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .backups-page table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
        text-align: left;
    }

    .backups-page table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 13px 14px;
        border-bottom: 1px solid var(--bk-border);
        white-space: nowrap;
    }

    .backups-page table td {
        padding: 14px;
        border-bottom: 1px solid #edf0f3;
        color: #475569;
        font-size: 11px;
        vertical-align: middle;
    }

    .backups-page table tbody tr:last-child td {
        border-bottom: 0;
    }

    .backups-page table tbody tr:hover {
        background: #fafcfb;
    }

    .backups-page .bk-company {
        color: var(--bk-text);
        font-weight: 700;
    }

    .backups-page .bk-size {
        font-weight: 700;
        color: #475569;
        white-space: nowrap;
    }

    .backups-page .bk-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }

    .backups-page .bk-status.completed {
        background: #ecfdf3;
        color: #128d51;
    }

    .backups-page .bk-status.failed {
        background: #fff1f2;
        color: #b42318;
    }

    .backups-page .bk-status.pending {
        background: #fff8e7;
        color: #a16207;
    }

    .backups-page .bk-status.other {
        background: #f1f5f9;
        color: #64748b;
    }

    .backups-page .bk-actions {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }

    .backups-page .bk-actions form {
        display: inline-flex;
        margin: 0;
    }

    .backups-page .bk-empty {
        text-align: center;
        padding: 32px 15px !important;
        color: var(--bk-muted) !important;
        font-size: 12px !important;
    }

    .backups-page .bk-pagination {
        padding-top: 18px;
    }

    .backups-page .bk-pagination nav {
        display: flex;
        justify-content: flex-end;
    }

    .backups-page .bk-pagination svg {
        width: 16px;
        height: 16px;
    }

    .backups-page .bk-pagination a,
    .backups-page .bk-pagination span {
        font-size: 11px;
    }

    @media (max-width: 600px) {
        .backups-page .bk-header h1 {
            font-size: 20px;
        }

        .backups-page .bk-section {
            padding: 16px;
        }

        .backups-page .bk-form-row {
            align-items: stretch;
            flex-direction: column;
        }

        .backups-page .bk-form-row .bk-btn {
            width: 100%;
        }

        .backups-page .bk-filter .bk-field {
            max-width: none;
            width: 100%;
        }
    }
</style>

<div class="backups-page">

    {{-- Page heading --}}
    <div class="bk-header">
        <div>
            <h1>Backups</h1>
            <p>Manage company database backups and monitor backup history.</p>
        </div>

        <div class="bk-schedule">
            Daily Schedule · 02:00
        </div>
    </div>

    {{-- Backup information --}}
    <section class="bk-section">

        <div class="bk-section-header">
            <h2>Backup Configuration</h2>
            <span>Automated backup policy</span>
        </div>

        <div class="bk-form-row">
            <div class="bk-field">
                <label>Backup Schedule</label>
                <select disabled>
                    <option>Daily at 02:00</option>
                </select>
            </div>

            <div class="bk-field">
                <label>Retention Period</label>
                <select disabled>
                    <option>{{ $retention }} days</option>
                </select>
            </div>
        </div>

        <p style="font-size:11px;color:#7b8a9c;margin:15px 0 0;">
            Automated backups run daily at 02:00. Completed backups are retained
            for {{ $retention }} days.
        </p>

    </section>

    {{-- Manual backup --}}
    <section class="bk-section">

        <div class="bk-section-header">
            <h2>Create Manual Backup</h2>
            <span>Run a backup immediately</span>
        </div>

        <form method="POST" action="{{ route('super.backups.store') }}">
            @csrf

            <div class="bk-form-row">
                <div class="bk-field">
                    <label for="backup_tenant">Select Company</label>

                    <select name="tenant_id" id="backup_tenant">
                        <option value="">All companies</option>

                        @foreach ($tenants as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="bk-btn bk-btn-primary">
                    + Backup Now
                </button>
            </div>
        </form>

    </section>

    {{-- Backup history --}}
    <section class="bk-section">

        <div class="bk-section-header">
            <h2>Backup History</h2>
            <span>{{ $backups->total() }} recorded backups</span>
        </div>

        {{-- Filter --}}
        <form method="GET" class="bk-filter">
            <div class="bk-field">
                <label for="backup_filter">Filter by Company</label>

                <select name="tenant_id" id="backup_filter"
                        onchange="this.form.submit()">
                    <option value="">All companies</option>

                    @foreach ($tenants as $t)
                        <option value="{{ $t }}"
                            @selected(request('tenant_id') === $t)>
                            {{ $t }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if (request()->filled('tenant_id'))
                <a href="{{ url()->current() }}" class="bk-btn bk-btn-light">
                    Clear Filter
                </a>
            @endif
        </form>

        <div style="height:20px;"></div>

        {{-- Table --}}
        <div class="bk-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Company</th>
                        <th>Backup Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($backups as $b)
                        <tr>
                            <td>
                                {{ $b->created_at->format('d M Y, H:i') }}
                            </td>

                            <td>
                                <span class="bk-company">
                                    {{ $b->tenant_id }}
                                </span>
                            </td>

                            <td>
                                <span class="bk-size">
                                    {{ number_format($b->size / 1024, 1) }} KB
                                </span>
                            </td>

                            <td title="{{ $b->error }}">
                                @php
                                    $statusClass = match ($b->status) {
                                        'completed' => 'completed',
                                        'failed' => 'failed',
                                        'pending', 'processing' => 'pending',
                                        default => 'other',
                                    };
                                @endphp

                                <span class="bk-status {{ $statusClass }}">
                                    ● {{ ucfirst($b->status) }}
                                </span>
                            </td>

                            <td>
                                <div class="bk-actions">

                                    @if ($b->status === 'completed')
                                        <a href="{{ route('super.backups.download', $b) }}"
                                           class="bk-btn bk-btn-light">
                                            Download
                                        </a>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('super.backups.destroy', $b) }}"
                                          onsubmit="return confirm('Delete this backup?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="bk-btn bk-btn-danger">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="bk-empty">
                                No backups found. Create your first backup to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bk-pagination">
            {{ $backups->links() }}
        </div>

    </section>

</div>

@endsection
