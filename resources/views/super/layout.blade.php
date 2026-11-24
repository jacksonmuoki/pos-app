
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
            --nav-bg: #0b1c2b;
            --nav-bg-hover: #142b3b;
            --nav-text: #eef2f7;
            --nav-muted: #b0c0d0;
            --nav-active-bg: rgba(255,255,255,.09);
            --nav-accent: #2563eb;
            --nav-section: #6b7f91;

            --ink: #1e293b;
            --muted: #7b8a9c;
            --line: #e6eaef;
            --surface: #ffffff;
            --page: #f5f7fa;

            --sidebar-width: 260px;
            --radius-md: 12px;
            --radius-sm: 10px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,.02), 0 1px 3px rgba(0,0,0,.03);
            --shadow-card: 0 4px 12px rgba(0,0,0,.02), 0 1px 2px rgba(0,0,0,.03);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, input {
            font: inherit;
        }

        /* SIDEBAR */
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
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,.1);
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
            color: #fff;
            border: 1px solid rgba(255,255,255,.08);
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
            transition: all .15s;
            position: relative;
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.06);
        }

        .nav-link.active {
            color: #fff;
            background: var(--nav-active-bg);
            box-shadow: inset 3px 0 var(--nav-accent);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
            opacity: .85;
            flex-shrink: 0;
        }

        .sidebar-bottom {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,.08);
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
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            border: 1px solid rgba(255,255,255,.1);
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
            border: 1px solid rgba(255,255,255,.12);
            background: transparent;
            color: var(--nav-text);
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            transition: background .15s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,.06);
        }

        /* MAIN AREA */
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
            position: relative;
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
            box-shadow: 0 0 0 2px rgba(43,122,90,.15);
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

        /* RESPONSIVE */
        @media(max-width:1200px) {
            .topbar-search {
                display: none;
            }
        }

        @media(max-width:1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .overlay.show {
                display: block;
            }

            .main-area {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .topbar {
                padding: 0 20px;
            }

            .content {
                padding: 24px 20px 40px;
            }
        }

        @media(max-width:600px) {
            .topbar {
                padding: 0 16px;
            }

            .content {
                padding: 20px 16px 36px;
            }

            .system-status {
                display: none;
            }

            .breadcrumb {
                font-size: 11px;
            }

            .topbar-right {
                gap: 10px;
            }
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

    <a href="{{ route('super.subscriptions.index') }}"
       class="nav-link {{ request()->routeIs('super.subscriptions.*') ? 'active' : '' }}">
        <span class="nav-icon">◈</span> Subscriptions
    </a>

    <a href="{{ route('super.transactions.index') }}"
       class="nav-link {{ request()->routeIs('super.transactions.*') ? 'active' : '' }}">
        <span class="nav-icon">▤</span> Transactions
    </a>

    <a href="{{ route('super.plans.index') }}"
       class="nav-link {{ request()->routeIs('super.plans.*') ? 'active' : '' }}">
        <span class="nav-icon">☰</span> Plans &amp; Pricing
    </a>

    <a href="{{ route('super.reports.index') }}"
       class="nav-link {{ request()->routeIs('super.reports.*') ? 'active' : '' }}">
        <span class="nav-icon">▥</span> Reports
    </a>

    <a href="{{ route('super.invoices.index') }}"
       class="nav-link {{ request()->routeIs('super.invoices.*') ? 'active' : '' }}">
        <span class="nav-icon">◧</span> Invoices &amp; Billing
    </a>

    <div class="nav-label">Administration</div>

    <a href="{{ route('super.admins.index') }}"
       class="nav-link {{ request()->routeIs('super.admins.*') ? 'active' : '' }}">
        <span class="nav-icon">♙</span> Administrators
    </a>

    <a href="{{ route('super.activity') }}"
       class="nav-link {{ request()->routeIs('super.activity') ? 'active' : '' }}">
        <span class="nav-icon">◷</span> Activity logs
    </a>

    <div class="nav-label">System</div>

    <a href="{{ route('super.settings') }}"
       class="nav-link {{ request()->routeIs('super.settings*') ? 'active' : '' }}">
        <span class="nav-icon">⚙</span> Settings
    </a>

    {{-- This link uses the integration route name from your existing routes.
         If it is not registered, add the route before enabling this link. --}}
    @if(\Illuminate\Support\Facades\Route::has('super.integrations'))
        <a href="{{ route('super.integrations') }}"
           class="nav-link {{ request()->routeIs('super.integrations*') ? 'active' : '' }}">
            <span class="nav-icon">⟐</span> Integrations
        </a>
    @endif

    @if(\Illuminate\Support\Facades\Route::has('super.webhooks.index'))
        <a href="{{ route('super.webhooks.index') }}"
           class="nav-link {{ request()->routeIs('super.webhooks*') ? 'active' : '' }}">
            <span class="nav-icon">⤳</span> Webhooks
        </a>
    @endif

    @if(\Illuminate\Support\Facades\Route::has('super.health'))
        <a href="{{ route('super.health') }}"
           class="nav-link {{ request()->routeIs('super.health*') ? 'active' : '' }}">
            <span class="nav-icon">♥</span> System health
        </a>
    @endif

    @if(\Illuminate\Support\Facades\Route::has('super.backups'))
        <a href="{{ route('super.backups') }}"
           class="nav-link {{ request()->routeIs('super.backups*') ? 'active' : '' }}">
            <span class="nav-icon">⛁</span> Backups
        </a>
    @endif

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
                <div class="admin-name">
                    {{ auth('super')->user()->name }}
                </div>
                <div class="admin-role">Super Administrator</div>
            </div>
        </div>

        <form method="POST" action="{{ route('super.logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                ↗ &nbsp; Sign out
            </button>
        </form>

    </div>
</aside>
@endauth

<div class="overlay" id="overlay"></div>

<div class="main-area">

    <header class="topbar">

        <div class="topbar-left">
            <button class="menu-toggle"
                    id="menuToggle"
                    aria-label="Open navigation">
                ☰
            </button>

            <div class="breadcrumb">
                Platform / <strong>@yield('breadcrumb', 'Overview')</strong>
            </div>
        </div>

        <div class="topbar-right">

            <div class="topbar-search">
                <span style="opacity:.5;font-size:12px">⌕</span>
                <input type="text"
                       placeholder="Search companies, admins…"
                       aria-label="Search">
            </div>

            <button class="icon-btn"
                    type="button"
                    aria-label="Notifications"
                    title="Notifications">
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
            <div class="flash success">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="flash error">
                {{ session('error') }}
            </div>
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
