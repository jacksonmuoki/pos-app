@extends('super.layout')

@section('title', 'Platform Overview')
@section('breadcrumb', 'Overview')

@section('content')

<div class="page-heading">
    <div>
        <h1>Platform overview</h1>
        <p>Monitor your POS platform and manage registered businesses.</p>
    </div>

    <a href="{{ route('super.companies.create') }}" class="btn btn-primary">
        + Register company
    </a>
</div>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-top">
            <span>Total companies</span>
            <div class="stat-icon green">▦</div>
        </div>
        <div class="stat-number">{{ number_format($totalCompanies) }}</div>
        <div class="stat-caption">All registered businesses</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Active companies</span>
            <div class="stat-icon blue">✓</div>
        </div>
        <div class="stat-number">{{ number_format($activeCompanies) }}</div>
        <div class="stat-caption">Currently active tenants</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Suspended companies</span>
            <div class="stat-icon red">⊘</div>
        </div>
        <div class="stat-number">{{ number_format($suspendedCompanies) }}</div>
        <div class="stat-caption">Restricted tenant accounts</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span>Platform status</span>
            <div class="stat-icon green">●</div>
        </div>
        <div class="stat-number" style="font-size:23px; letter-spacing:-0.3px;">Operational</div>
        <div class="stat-caption">Application status indicator</div>
    </div>
</div>

<div class="dashboard-columns">

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h3>Recently registered companies</h3>
                <p>Your latest tenant registrations</p>
            </div>
            <a href="{{ route('super.companies.index') }}" class="text-link">
                View all →
            </a>
        </div>

        <div class="company-list">
            @forelse($recentCompanies as $company)
                <div class="company-row">
                    <div class="company-symbol">
                        {{ strtoupper(substr($company->name ?? $company->id, 0, 1)) }}
                    </div>

                    <div class="company-info">
                        <strong>{{ $company->name ?? $company->id }}</strong>
                        <span>{{ $company->id }}</span>
                    </div>

                    <div class="company-status">
                        @if($company->suspended)
                            <span class="status-badge suspended">Suspended</span>
                        @else
                            <span class="status-badge active">Active</span>
                        @endif
                    </div>

                    <div class="company-date">
                        {{ $company->created_at?->format('d M Y') ?? '—' }}
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">▦</div>
                    <strong>No companies registered yet</strong>
                    <p>Register your first company to get started.</p>
                    <a href="{{ route('super.companies.create') }}" class="btn">
                        Register company
                    </a>
                </div>
            @endforelse
        </div>
    </section>

    <section class="panel quick-panel">
        <div class="panel-heading">
            <div>
                <h3>Quick actions</h3>
                <p>Common platform operations</p>
            </div>
        </div>

        <a href="{{ route('super.companies.create') }}" class="quick-action">
            <div class="quick-icon">＋</div>
            <div>
                <strong>Register a company</strong>
                <span>Create a new tenant workspace</span>
            </div>
            <b>→</b>
        </a>

        <a href="{{ route('super.companies.index') }}" class="quick-action">
            <div class="quick-icon">▦</div>
            <div>
                <strong>Manage companies</strong>
                <span>Review and manage tenants</span>
            </div>
            <b>→</b>
        </a>

        <div class="platform-note">
            <div class="note-icon">◆</div>
            <div>
                <strong>Platform administration</strong>
                <p>Manage company workspaces centrally while keeping each business's records isolated.</p>
            </div>
        </div>
    </section>

</div>

@endsection

@push('styles')
<style>
    /* ---------- PROFESSIONAL TOKENS ---------- */
    :root {
        --bg-page: #f5f7fa;
        --surface: #ffffff;
        --border-light: #e6eaef;
        --border-mid: #d9dee6;
        --text-primary: #1e293b;
        --text-secondary: #4a5a6e;
        --text-muted: #7b8a9c;
        --nav-bg: #0b1c2b;
        --nav-text: #eef2f7;
        --nav-muted: #b0c0d0;
        --accent-blue: #2563eb;
        --accent-green-soft: #e8f0eb;
        --accent-blue-soft: #e8effa;
        --accent-red-soft: #f8ecec;
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.02), 0 1px 3px rgba(0, 0, 0, 0.03);
        --shadow-card: 0 4px 12px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.03);
        --radius-md: 12px;
        --radius-sm: 10px;
        --radius-pill: 50px;
        --green-700: #1b6b4c;
        --green-600: #2b7a5a;
        --green-50: #e6f0eb;
        --line: #e9edf2;
    }

    /* ---------- NAVIGATION BAR — ALL FEATURES ---------- */
    .navbar {
        background: var(--nav-bg);
        color: var(--nav-text);
        padding: 0 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 68px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 700;
        font-size: 18px;
        letter-spacing: -0.3px;
    }

    .nav-brand .brand-icon {
        width: 34px;
        height: 34px;
        background: #1f3a4b;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: 16px;
        color: #ffffff;
        font-weight: 800;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
        margin: 0 20px;
    }

    .nav-links a {
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
        color: var(--nav-muted);
        border-radius: 40px;
        transition: all 0.15s;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .nav-links a:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #ffffff;
    }

    .nav-links a.active {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .nav-links i {
        font-style: normal;
        font-size: 14px;
        opacity: 0.75;
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .nav-right .user-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.05);
        padding: 6px 14px 6px 10px;
        border-radius: 40px;
        border: 1px solid rgba(255, 255, 255, 0.06);
        font-size: 12px;
        font-weight: 600;
    }

    .user-badge .avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #2d4b5e;
        display: grid;
        place-items: center;
        font-size: 12px;
        font-weight: 700;
        color: #ffffff;
    }

    /* ---------- PAGE HEADING ---------- */
    .page-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .page-heading h1 {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.6px;
        color: var(--text-primary);
        margin: 0;
    }

    .page-heading p {
        font-size: 13px;
        color: var(--text-secondary);
        margin-top: 4px;
        font-weight: 500;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        background: #ffffff;
        border: 1px solid var(--border-mid);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
        transition: all 0.15s;
        box-shadow: var(--shadow-sm);
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
    }

    .btn:hover {
        background: #f9fbfd;
        border-color: #bcc6d2;
    }

    .btn-primary {
        background: var(--nav-bg);
        color: #ffffff;
        border: 1px solid var(--nav-bg);
    }

    .btn-primary:hover {
        background: #142b3b;
        border-color: #142b3b;
    }

    /* ---------- STATS GRID ---------- */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        padding: 20px 22px;
        box-shadow: var(--shadow-card);
        transition: border 0.2s;
        min-width: 0;
    }

    .stat-card:hover {
        border-color: var(--border-mid);
    }

    .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 800;
        background: #f1f4f9;
        color: var(--text-secondary);
        border: 1px solid #e9edf3;
    }

    .stat-icon.green {
        background: var(--accent-green-soft);
        color: var(--green-700);
        border-color: #d4e3da;
    }
    .stat-icon.blue {
        background: var(--accent-blue-soft);
        color: var(--accent-blue);
        border-color: #d0ddee;
    }
    .stat-icon.red {
        background: var(--accent-red-soft);
        color: #a13e3e;
        border-color: #ecd4d4;
    }

    .stat-number {
        font-family: 'Manrope', sans-serif;
        font-weight: 800;
        font-size: 32px;
        letter-spacing: -1px;
        margin-top: 16px;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .stat-caption {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 4px;
        font-weight: 500;
    }

    /* ---------- TWO‑COLUMN PANELS ---------- */
    .dashboard-columns {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(280px, .85fr);
        gap: 22px;
        align-items: start;
    }

    .panel {
        background: var(--surface);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .panel-heading {
        padding: 20px 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid var(--line);
        background: #ffffff;
    }

    .panel-heading h3 {
        font-family: 'Manrope', sans-serif;
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.2px;
    }

    .panel-heading p {
        margin: 4px 0 0;
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .text-link {
        color: var(--accent-blue);
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        letter-spacing: 0.1px;
        transition: color 0.15s;
        text-decoration: none;
    }

    .text-link:hover {
        color: #1d4ed8;
    }

    /* ---------- COMPANY LIST ---------- */
    .company-row {
        padding: 14px 22px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-bottom: 1px solid #f0f3f7;
        transition: background 0.1s;
    }

    .company-row:last-child {
        border-bottom: 0;
    }

    .company-row:hover {
        background: #fafcff;
    }

    .company-symbol {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        border-radius: 10px;
        background: #f1f5f9;
        color: #1e293b;
        display: grid;
        place-items: center;
        font-weight: 700;
        font-size: 15px;
        border: 1px solid #e2e8f0;
    }

    .company-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
        flex: 1;
    }

    .company-info strong {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .company-info span,
    .company-date {
        color: var(--text-muted);
        font-size: 10.5px;
        font-weight: 500;
    }

    .company-date {
        white-space: nowrap;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: var(--radius-pill);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.2px;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .status-badge.active {
        background: #eaf5ee;
        color: #1a6b3f;
        border-color: #d3e8da;
    }

    .status-badge.suspended {
        background: #fdf2e8;
        color: #9b5e2a;
        border-color: #f0dfcf;
    }

    /* ---------- QUICK ACTIONS ---------- */
    .quick-action {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 17px 22px;
        border-bottom: 1px solid #eff2f7;
        transition: background 0.15s;
        text-decoration: none;
        color: inherit;
    }

    .quick-action:hover {
        background: #f8fbfe;
    }

    .quick-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        background: #f1f5f9;
        color: #1e293b;
        border-radius: 9px;
        font-size: 18px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .quick-action div:nth-child(2) {
        flex: 1;
    }

    .quick-action strong {
        display: block;
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .quick-action span {
        display: block;
        color: var(--text-muted);
        font-size: 10.5px;
        margin-top: 3px;
        font-weight: 500;
    }

    .quick-action b {
        color: #a0b0c0;
        font-size: 16px;
        font-weight: 400;
    }

    /* platform note — clean, neutral */
    .platform-note {
        display: flex;
        gap: 12px;
        margin: 18px 20px 22px;
        padding: 16px 18px;
        background: #f7faff;
        border-radius: 10px;
        border: 1px solid #e7edf6;
    }

    .note-icon {
        color: var(--accent-blue);
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
    }

    .platform-note strong {
        font-size: 11.5px;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: 0.1px;
    }

    .platform-note p {
        color: #4e6277;
        font-size: 10.5px;
        line-height: 1.7;
        margin: 5px 0 0;
        font-weight: 500;
    }

    /* ---------- EMPTY STATE ---------- */
    .empty-state {
        padding: 48px 24px;
        text-align: center;
    }

    .empty-icon {
        font-size: 32px;
        color: #b8c8d8;
        margin-bottom: 12px;
        font-weight: 400;
    }

    .empty-state strong {
        display: block;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .empty-state p {
        color: var(--text-muted);
        font-size: 12px;
        margin: 8px 0 20px;
        font-weight: 500;
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 1200px) {
        .navbar {
            padding: 0 24px;
        }
        .nav-links a {
            padding: 6px 10px;
            font-size: 12px;
        }
    }

    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .dashboard-columns {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .nav-links {
            display: none;
        }
        .navbar {
            padding: 0 18px;
        }
    }

    @media (max-width: 600px) {
        .dashboard-grid {
            gap: 12px;
        }
        .stat-card {
            padding: 16px;
        }
        .stat-number {
            font-size: 26px;
        }
        .company-date {
            display: none;
        }
        .company-row {
            padding: 12px 16px;
            gap: 10px;
        }
        .page-heading h1 {
            font-size: 22px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Navigation active state (optional — assumes your layout handles it)
    // This is only for standalone preview; in production the layout usually sets active states.
    document.addEventListener('DOMContentLoaded', function () {
        const navLinks = document.querySelectorAll('.nav-links a');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === window.location.pathname) {
                link.classList.add('active');
            }
        });
    });
</script>
@endpush