<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | POS Platform</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* professional, neutral, corporate palette */
            --nav-bg: #0b1c2b;
            --nav-bg-hover: #142b3b;
            --nav-text: #eef2f7;
            --nav-muted: #b0c0d0;
            --nav-active-bg: rgba(255, 255, 255, 0.09);
            --nav-accent: #2563eb;
            --nav-section: #6b7f91;

            --ink: #1e293b;
            --muted: #7b8a9c;
            --line: #e6eaef;
            --line-soft: #f0f3f7;
            --surface: #ffffff;
            --page: #f5f7fa;
            --red: #a13e3e;
            --amber: #9b5e2a;
            --green-700: #1b6b4c;
            --green-50: #e6f0eb;

            --sidebar-width: 260px;
            --radius-md: 12px;
            --radius-sm: 10px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.02), 0 1px 3px rgba(0, 0, 0, 0.03);
            --shadow-card: 0 4px 12px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }

        /* ---------- SIDEBAR ---------- */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            inset: 0 auto 0 0;
            background: var(--nav-bg);
            color: var(--nav-text);
            display: flex;
            flex-direction: column;
            z-index: 50;
            padding: 24px 16px;
            transition: transform .25s ease;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 28px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            background: #1f3a4b;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-name {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: -.5px;
        }

        .brand-sub {
            color: var(--nav-muted);
            font-size: 10px;
            margin-top: 3px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .nav-label {
            color: var(--nav-section);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.3px;
            padding: 18px 12px 8px;
            text-transform: uppercase;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 13px;
            margin: 1px 0;
            color: var(--nav-muted);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.15s;
            position: relative;
        }

        .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        .nav-link.active {
            color: #ffffff;
            background: var(--nav-active-bg);
            box-shadow: inset 3px 0 var(--nav-accent);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
            opacity: 0.85;
            flex-shrink: 0;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 9.5px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 20px;
            background: rgba(37, 99, 235, 0.18);
            color: #8ab4f8;
            letter-spacing: 0.2px;
        }

        .sidebar-bottom {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 16px;
            position: sticky;
            bottom: 0;
            background: var(--nav-bg);
            padding-bottom: 4px;
        }

        .admin-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 5px 14px;
        }

        .avatar {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #2d4b5e;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .admin-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--nav-text);
        }

        .admin-role {
            font-size: 10.5px;
            color: var(--nav-muted);
            margin-top: 2px;
        }

        .logout-btn {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: transparent;
            color: var(--nav-text);
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.15s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        /* ---------- MAIN AREA ---------- */
        .main-area {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            height: 68px;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .breadcrumb {
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
        }

        .breadcrumb strong {
            color: var(--ink);
            font-weight: 700;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .topbar-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f4f9;
            border: 1px solid var(--line);
            border-radius: 40px;
            padding: 6px 14px;
            min-width: 200px;
        }

        .topbar-search input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 12px;
            color: var(--ink);
            width: 100%;
        }

        .topbar-search input::placeholder {
            color: #a6b2c0;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #f1f4f9;
            border: 1px solid var(--line);
            color: var(--muted);
            cursor: pointer;
            font-size: 14px;
            transition: background 0.15s, color 0.15s;
            position: relative;
        }

        .icon-btn:hover {
            background: #e9edf3;
            color: var(--ink);
        }

        .icon-btn .notif-dot {
            position: absolute;
            top: 7px;
            right: 8px;
            width: 6px;
            height: 6px;
            background: #c74747;
            border-radius: 50%;
            border: 1.5px solid var(--surface);
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            padding: 6px 12px;
            background: #f1f4f9;
            border-radius: 40px;
            border: 1px solid #e6eaef;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #2b7a5a;
            box-shadow: 0 0 0 2px rgba(43, 122, 90, 0.15);
        }

        .top-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e8effa;
            color: #2563eb;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 13px;
            border: 1px solid #d0ddee;
        }

        .menu-toggle {
            display: none;
            border: 0;
            background: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--ink);
            padding: 4px;
        }

        .content {
            max-width: 1600px;
            padding: 32px 32px 50px;
            margin: auto;
        }

        .flash {
            padding: 13px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid transparent;
        }

        .flash.success {
            background: #eaf5ee;
            color: #1a6b3f;
            border-color: #d3e8da;
        }

        .flash.error {
            background: #fdf2e8;
            color: #9b5e2a;
            border-color: #f0dfcf;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 27px;
            flex-wrap: wrap;
        }

        .page-heading h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 25px;
            letter-spacing: -.6px;
            margin: 0;
            font-weight: 800;
            color: var(--ink);
        }

        .page-heading p {
            color: var(--muted);
            margin: 6px 0 0;
            font-size: 13px;
            font-weight: 500;
        }

        .btn {
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--ink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12.5px;
            transition: all 0.15s;
            box-shadow: var(--shadow-sm);
            line-height: 1;
        }

        .btn:hover {
            background: #f9fbfd;
            border-color: #bcc6d2;
        }

        .btn-primary {
            background: var(--nav-bg);
            color: #ffffff;
            border-color: var(--nav-bg);
        }

        .btn-primary:hover {
            background: var(--nav-bg-hover);
            border-color: var(--nav-bg-hover);
        }

        .btn-light {
            background: #f1f4f9;
            color: var(--ink);
            border-color: #e6eaef;
        }

        .btn-light:hover {
            background: #e9edf3;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-card);
        }

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 40;
        }

        /* ---------- RESPONSIVE ---------- */
        @media(max-width: 1200px) {
            .topbar-search { display: none; }
        }

        @media(max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .overlay.show { display: block; }
            .main-area { margin-left: 0; }
            .menu-toggle { display: block; }
            .topbar { padding: 0 20px; }
            .content { padding: 24px 20px 40px; }
        }

        @media(max-width: 600px) {
            .topbar { padding: 0 16px; }
            .content { padding: 20px 16px 36px; }
            .system-status { display: none; }
            .page-heading { align-items: flex-start; flex-direction: column; }
            .page-heading h1 { font-size: 22px; }
            .breadcrumb { font-size: 11px; }
            .topbar-right { gap: 10px; }
        }
    </style>
    @stack('styles')
</head>
<body>

@auth('super')
<aside class="sidebar" id="sidebar">
    <a href="{{ route('super.home') }}" class="brand">
        <div class="brand-icon">P</div>
        <div>
            <div class="brand-name">POS Platform</div>
            <div class="brand-sub">Administration</div>
        </div>
    </a>

    <div class="nav-label">Workspace</div>

    <a href="{{ route('super.home') }}"
       class="nav-link {{ request()->routeIs('super.home') ? 'active' : '' }}">
        <span class="nav-icon">◫</span> Overview
    </a>

    <a href="{{ route('super.companies.index') }}"
       class="nav-link {{ request()->routeIs('super.companies.*') ? 'active' : '' }}">
        <span class="nav-icon">▦</span> Companies
    </a>

    <a href="{{ route('super.companies.create') }}"
       class="nav-link {{ request()->routeIs('super.companies.create') ? 'active' : '' }}">
        <span class="nav-icon">＋</span> Register company
    </a>

    <div class="nav-label">Platform</div>

    <a href="#" class="nav-link" title="Subscriptions">
        <span class="nav-icon">◈</span> Subscriptions
    </a>
    <a href="#" class="nav-link" title="Transactions">
        <span class="nav-icon">▤</span> Transactions
    </a>
    <a href="#" class="nav-link" title="Plans & pricing">
        <span class="nav-icon">☰</span> Plans
    </a>
    <a href="#" class="nav-link" title="Revenue reports">
        <span class="nav-icon">▥</span> Reports
    </a>
    <a href="#" class="nav-link" title="Invoices & billing">
        <span class="nav-icon">◧</span> Invoices
    </a>

    <div class="nav-label">Administration</div>

    <a href="#" class="nav-link" title="Administrators">
        <span class="nav-icon">♙</span> Administrators
    </a>
    <a href="#" class="nav-link" title="Roles & permissions">
        <span class="nav-icon">⚿</span> Roles
    </a>
    <a href="{{ route('super.activity') }}"
   class="nav-link {{ request()->routeIs('super.activity') ? 'active' : '' }}">
    <span class="nav-icon">◷</span> Activity logs
</a>
    <a href="#" class="nav-link" title="Audit trail">
        <span class="nav-icon">🔍</span> Audit trail
    </a>

    <div class="nav-label">System</div>

    <a href="#" class="nav-link" title="Settings">
        <span class="nav-icon">⚙</span> Settings
    </a>
    <a href="#" class="nav-link" title="API & integrations">
        <span class="nav-icon">⟐</span> Integrations
    </a>
    <a href="#" class="nav-link" title="Webhooks">
        <span class="nav-icon">⤳</span> Webhooks
    </a>
    <a href="#" class="nav-link" title="System health">
        <span class="nav-icon">♥</span> System health
    </a>
    <a href="#" class="nav-link" title="Backups">
        <span class="nav-icon">⛁</span> Backups
    </a>

    <div class="nav-label">Support</div>

    <a href="#" class="nav-link" title="Help center">
        <span class="nav-icon">?</span> Help center
    </a>
    <a href="#" class="nav-link" title="Contact support">
        <span class="nav-icon">✉</span> Contact support
    </a>

    <div class="sidebar-bottom">
        <div class="admin-mini">
            <div class="avatar">
                {{ strtoupper(substr(auth('super')->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div>
                <div class="admin-name">{{ auth('super')->user()->name }}</div>
                <div class="admin-role">Super Administrator</div>
            </div>
        </div>

        <form method="POST" action="{{ route('super.logout') }}">
            @csrf
            <button class="logout-btn">↗ &nbsp; Sign out</button>
        </form>
    </div>
</aside>
@endauth

<div class="overlay" id="overlay"></div>

<div class="main-area">
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle" aria-label="Open navigation">☰</button>
            <div class="breadcrumb">
                Platform / <strong>@yield('breadcrumb', 'Overview')</strong>
            </div>
        </div>

        <div class="topbar-right">
            <div class="topbar-search">
                <span style="opacity:.5;font-size:12px">⌕</span>
                <input type="text" placeholder="Search companies, admins…" aria-label="Search">
            </div>

            <button class="icon-btn" aria-label="Notifications" title="Notifications">
                ✦
                <span class="notif-dot"></span>
            </button>

            <div class="system-status">
                <span class="status-dot"></span>
                Platform workspace
            </div>

            @auth('super')
                <div class="top-avatar">
                    {{ strtoupper(substr(auth('super')->user()->name ?? 'A', 0, 1)) }}
                </div>
            @endauth
        </div>
    </header>

    <main class="content">
        @if(session('status'))
            <div class="flash success">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="flash error">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuToggle = document.getElementById('menuToggle');

    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        overlay?.classList.toggle('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar?.classList.remove('open');
        overlay.classList.remove('show');
    });
</script>

@stack('scripts')
</body>
</html>