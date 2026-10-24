
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c1510" id="metaTheme">

    <title>Dashboard · {{ config('app.name', 'POS') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        (function () {
            try {
                const theme = localStorage.getItem('pos-admin-theme') || 'dark';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <style>
        :root {
            --bg: #f4f7f5;
            --surface: #ffffff;
            --surface-2: #f8faf9;
            --border: #e2e8e4;
            --border-strong: #cbd5ce;
            --text: #17231b;
            --text-2: #526157;
            --text-3: #77847b;
            --text-muted: #98a39b;

            --primary: #15803d;
            --primary-hover: #166534;
            --primary-soft: #e9f7ee;

            --success: #047857;
            --success-soft: #e8f8ef;
            --danger: #b91c1c;
            --danger-soft: #fef0f0;
            --warning: #b45309;
            --warning-soft: #fff7e6;
            --info: #0369a1;
            --info-soft: #eff8ff;
            --purple: #6d28d9;
            --purple-soft: #f4efff;

            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;

            --shadow-sm: 0 1px 2px rgba(17,24,39,.04);
            --shadow: 0 1px 3px rgba(17,24,39,.06);

            --font: 'Inter', system-ui, sans-serif;
            --sidebar-w: 250px;
            --topbar-h: 66px;
        }

        [data-theme="dark"] {
            --bg: #0c1510;
            --surface: #111d16;
            --surface-2: #17251c;
            --border: #26372b;
            --border-strong: #35483a;
            --text: #edf5ef;
            --text-2: #c3d0c6;
            --text-3: #92a397;
            --text-muted: #6f8375;

            --primary: #4ade80;
            --primary-hover: #86efac;
            --primary-soft: #163a25;

            --success: #34d399;
            --success-soft: #123527;
            --danger: #f87171;
            --danger-soft: #3a1b1b;
            --warning: #fbbf24;
            --warning-soft: #3a2d13;
            --info: #7dd3fc;
            --info-soft: #122f40;
            --purple: #c4b5fd;
            --purple-soft: #2d2144;

            --shadow-sm: 0 1px 2px rgba(0,0,0,.3);
            --shadow: 0 1px 3px rgba(0,0,0,.4);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: var(--font);
            font-size: 14px;
            line-height: 1.5;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        button, input {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
        }

        /* Application layout */

        .shell {
            display: grid;
            grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 22px 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            z-index: 60;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 8px;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            background: linear-gradient(135deg, #22c55e, #166534);
            box-shadow: 0 5px 12px rgba(22,101,52,.2);
        }

        .brand-name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .brand-tag {
            display: block;
            font-size: 10px;
            color: var(--text-3);
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 23px;
        }

        .nav-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-label {
            padding: 0 11px 8px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            min-height: 42px;
            padding: 10px 11px;
            border-radius: 9px;
            color: var(--text-2);
            font-size: 13px;
            font-weight: 550;
            transition: background .18s, color .18s;
        }

        .nav-link:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 700;
        }

        .nav-icon {
            width: 19px;
            height: 19px;
            display: grid;
            place-items: center;
            font-size: 16px;
        }

        .nav-link.disabled {
            opacity: .48;
            cursor: not-allowed;
        }

        .coming {
            margin-left: auto;
            font-size: 9px;
            font-weight: 700;
            color: var(--text-3);
            background: var(--surface-2);
            border: 1px solid var(--border);
            padding: 3px 6px;
            border-radius: 5px;
        }

        .sidebar-foot {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding: 16px 5px 0;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .avatar {
            width: 36px;
            height: 36px;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 800;
            font-size: 12px;
        }

        .profile-info {
            min-width: 0;
        }

        .profile-name {
            font-size: 12px;
            font-weight: 700;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .profile-role {
            font-size: 11px;
            color: var(--text-3);
        }

        /* Main content */

        .main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: var(--topbar-h);
            position: sticky;
            top: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 0 30px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .topbar-title {
            font-size: 13px;
            color: var(--text-2);
            font-weight: 600;
        }

        .search {
            position: relative;
            width: min(340px, 36vw);
        }

        .search input {
            width: 100%;
            height: 38px;
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--text);
            border-radius: 9px;
            padding: 0 42px 0 13px;
            font-size: 12px;
        }

        .search input::placeholder {
            color: var(--text-3);
        }

        .search-shortcut {
            position: absolute;
            right: 10px;
            top: 9px;
            font-size: 10px;
            color: var(--text-3);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 1px 5px;
        }

        .icon-button {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            color: var(--text-2);
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 16px;
        }

        .icon-button:hover {
            background: var(--surface-2);
            color: var(--primary);
        }

        .menu-toggle {
            display: none;
        }

        .content {
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px;
            flex: 1;
        }

        /* Page heading */

        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 27px;
        }

        .eyebrow {
            color: var(--primary);
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: .12em;
        }

        .hero h1 {
            margin: 5px 0 7px;
            font-size: clamp(25px, 3vw, 32px);
            line-height: 1.2;
            letter-spacing: -.04em;
            font-weight: 800;
        }

        .hero p {
            color: var(--text-3);
            font-size: 13px;
        }

        .hero-date {
            padding: 9px 13px;
            border: 1px solid var(--border);
            background: var(--surface);
            border-radius: 9px;
            color: var(--text-2);
            font-size: 12px;
        }

        /* Notice */

        .setup-notice {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 16px 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            margin-bottom: 23px;
        }

        .notice-icon {
            width: 35px;
            height: 35px;
            flex: none;
            display: grid;
            place-items: center;
            background: var(--info-soft);
            color: var(--info);
            border-radius: 9px;
            font-size: 17px;
        }

        .notice-title {
            font-size: 13px;
            font-weight: 750;
            margin-bottom: 3px;
        }

        .notice-text {
            color: var(--text-3);
            font-size: 12px;
            line-height: 1.6;
        }

        /* Section headings */

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 27px 0 14px;
        }

        .section-heading h2 {
            font-size: 15px;
            font-weight: 750;
            letter-spacing: -.3px;
        }

        .section-heading p {
            margin-top: 3px;
            font-size: 11px;
            color: var(--text-3);
        }

        /* KPI cards */

        .kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
        }

        .kpi {
            min-width: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 19px;
            box-shadow: var(--shadow-sm);
        }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 17px;
        }

        .kpi-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            font-size: 16px;
        }

        .kpi-icon.green {
            background: var(--success-soft);
            color: var(--success);
        }

        .kpi-icon.blue {
            background: var(--info-soft);
            color: var(--info);
        }

        .kpi-icon.purple {
            background: var(--purple-soft);
            color: var(--purple);
        }

        .kpi-icon.orange {
            background: var(--warning-soft);
            color: var(--warning);
        }

        .kpi-status {
            font-size: 9px;
            font-weight: 700;
            color: var(--text-3);
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 4px 7px;
        }

        .kpi-label {
            font-size: 12px;
            color: var(--text-3);
            margin-bottom: 5px;
        }

        .kpi-value {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .kpi-foot {
            margin-top: 7px;
            color: var(--text-3);
            font-size: 10px;
        }

        /* Cards and shortcuts */

        .grid-two {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 1fr);
            gap: 17px;
        }

        .card {
            min-width: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 17px 19px;
            border-bottom: 1px solid var(--border);
        }

        .card-head h3 {
            font-size: 13px;
            font-weight: 750;
        }

        .card-head span {
            font-size: 10px;
            color: var(--text-3);
        }

        .card-body {
            padding: 19px;
        }

        .empty-state {
            min-height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 22px;
        }

        .empty-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            color: var(--text-3);
            font-size: 20px;
            margin-bottom: 12px;
        }

        .empty-state h4 {
            font-size: 13px;
            margin-bottom: 5px;
        }

        .empty-state p {
            max-width: 300px;
            color: var(--text-3);
            font-size: 11px;
            line-height: 1.6;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .quick-link {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 11px;
            border: 1px solid var(--border);
            background: var(--surface);
            border-radius: 10px;
            transition: border-color .18s, background .18s;
        }

        .quick-link:hover {
            border-color: var(--primary);
            background: var(--surface-2);
        }

        .quick-link-icon {
            width: 33px;
            height: 33px;
            flex: none;
            display: grid;
            place-items: center;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 9px;
            font-size: 15px;
        }

        .quick-link strong {
            display: block;
            font-size: 11px;
            font-weight: 700;
        }

        .quick-link small {
            display: block;
            color: var(--text-3);
            font-size: 9px;
            margin-top: 3px;
        }

        .quick-link.disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        /* Setup checklist */

        .setup-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .setup-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }

        .setup-item:last-child {
            border-bottom: 0;
        }

        .setup-number {
            width: 27px;
            height: 27px;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            color: var(--text-3);
            font-size: 11px;
            font-weight: 800;
        }

        .setup-item strong {
            display: block;
            font-size: 11px;
        }

        .setup-item small {
            display: block;
            color: var(--text-3);
            font-size: 10px;
            margin-top: 2px;
        }

        .status-label {
            margin-left: auto;
            color: var(--warning);
            background: var(--warning-soft);
            border-radius: 20px;
            padding: 4px 7px;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Footer */

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 17px 30px;
            border-top: 1px solid var(--border);
            color: var(--text-3);
            font-size: 10px;
        }

        .flash {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid var(--border);
        }

        .flash.error {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .logout-form {
            margin-top: 13px;
        }

        .logout-button {
            width: 100%;
            text-align: left;
            padding: 9px 10px;
            border-radius: 8px;
            color: var(--danger);
            font-size: 11px;
            font-weight: 650;
        }

        .logout-button:hover {
            background: var(--danger-soft);
        }

        .overlay {
            display: none;
        }

        /* Responsive */

        @media (max-width: 1150px) {
            .kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .grid-two {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 850px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 270px;
                max-width: 85vw;
                transform: translateX(-105%);
                transition: transform .25s ease;
                box-shadow: 15px 0 35px rgba(0,0,0,.15);
            }

            body.nav-open .sidebar {
                transform: translateX(0);
            }

            .overlay {
                position: fixed;
                inset: 0;
                z-index: 50;
                background: rgba(0,0,0,.5);
            }

            body.nav-open .overlay {
                display: block;
            }

            .menu-toggle {
                display: grid;
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 24px 18px;
            }

            .footer {
                padding: 16px 18px;
            }
        }

        @media (max-width: 560px) {
            .topbar {
                gap: 8px;
                padding: 0 13px;
            }

            .topbar-title {
                display: none;
            }

            .search {
                width: min(230px, 48vw);
            }

            .search-shortcut {
                display: none;
            }

            .content {
                padding: 21px 13px;
            }

            .hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-date {
                align-self: flex-start;
            }

            .kpis {
                gap: 10px;
            }

            .kpi {
                padding: 14px;
            }

            .kpi-value {
                font-size: 16px;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }

            .footer {
                padding: 14px 13px;
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

@php
    $shopName = config('app.name', 'POS');
    $userName = $user->name ?? 'User';
    $userRole = ucfirst($user->role ?? 'Staff');

    $nameParts = preg_split('/\s+/', trim($userName)) ?: [];
    $initials = strtoupper(
        mb_substr($nameParts[0] ?? 'U', 0, 1) .
        mb_substr($nameParts[1] ?? '', 0, 1)
    );

    $canManageStaff = method_exists($user, 'canManageStaff')
        ? $user->canManageStaff()
        : false;
@endphp

<div class="shell">

    {{-- Sidebar --}}
    <aside class="sidebar">

        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-mark">P</div>
            <div>
                <div class="brand-name">{{ $shopName }}</div>
                <span class="brand-tag">Management system</span>
            </div>
        </a>

        <nav class="nav">

            <div class="nav-group">
                <div class="nav-label">Overview</div>

                <a href="{{ route('dashboard') }}" class="nav-link active">
                    <span class="nav-icon">▦</span>
                    <span>Dashboard</span>
                </a>

                <span class="nav-link disabled" title="POS module is not configured yet">
                    <span class="nav-icon">▣</span>
                    <span>Point of sale</span>
                    <span class="coming">Soon</span>
                </span>
            </div>

            <div class="nav-group">
                <div class="nav-label">Trade</div>

                <span class="nav-link disabled">
                    <span class="nav-icon">↗</span>
                    <span>Sales</span>
                    <span class="coming">Soon</span>
                </span>

                <span class="nav-link disabled">
                    <span class="nav-icon">▤</span>
                    <span>Reports</span>
                    <span class="coming">Soon</span>
                </span>
            </div>

            <div class="nav-group">
                <div class="nav-label">Catalogue</div>

                <span class="nav-link disabled">
                    <span class="nav-icon">◇</span>
                    <span>Products</span>
                    <span class="coming">Soon</span>
                </span>

                <span class="nav-link disabled">
                    <span class="nav-icon">◈</span>
                    <span>Categories</span>
                    <span class="coming">Soon</span>
                </span>

                <span class="nav-link disabled">
                    <span class="nav-icon">▱</span>
                    <span>Inventory</span>
                    <span class="coming">Soon</span>
                </span>
            </div>

            <div class="nav-group">
                <div class="nav-label">Administration</div>

                @if($canManageStaff)
                    <a href="{{ route('staff.index') }}" class="nav-link">
                        <span class="nav-icon">♙</span>
                        <span>Staff management</span>
                    </a>
                @endif

                <span class="nav-link disabled">
                    <span class="nav-icon">⚙</span>
                    <span>Settings</span>
                    <span class="coming">Soon</span>
                </span>
            </div>

        </nav>

        <div class="sidebar-foot">

            <div class="profile">
                <div class="avatar">{{ $initials }}</div>

                <div class="profile-info">
                    <div class="profile-name">{{ $userName }}</div>
                    <div class="profile-role">{{ $userRole }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-button">
                    ↪ &nbsp; Sign out
                </button>
            </form>

        </div>

    </aside>

    <div class="overlay" id="menuOverlay"></div>

    {{-- Main --}}
    <div class="main">

        {{-- Topbar --}}
        <header class="topbar">

            <div class="topbar-left">
                <button type="button" class="icon-button menu-toggle" id="menuToggle"
                        aria-label="Open navigation" aria-expanded="false">
                    ☰
                </button>

                <span class="topbar-title">Company workspace</span>
            </div>

            <div class="topbar-right">

                <div class="search">
                    <input type="search"
                           id="searchInput"
                           placeholder="Search dashboard..."
                           autocomplete="off"
                           aria-label="Search dashboard">

                    <span class="search-shortcut">Ctrl K</span>
                </div>

                <button type="button" class="icon-button" id="themeToggle"
                        aria-label="Switch theme" title="Switch theme">
                    <span id="themeIcon">☀</span>
                </button>

            </div>

        </header>

         {{-- Footer --}}
        <footer class="footer">
            <span>
                © {{ date('Y') }} {{ $shopName }}. All rights reserved.
            </span>

            <span>
                Signed in as {{ $userName }} · {{ now()->format('j M Y, H:i') }}
            </span>
        </footer>

    </div>

</div>

<script>
(function () {
    'use strict';

    const root = document.documentElement;
    const body = document.body;

    // Theme switching
    const themeBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const metaTheme = document.getElementById('metaTheme');

    function syncTheme() {
        const dark = root.getAttribute('data-theme') === 'dark';

        themeIcon.textContent = dark ? '☀' : '☾';
        themeBtn.setAttribute(
            'aria-label',
            dark ? 'Switch to light mode' : 'Switch to dark mode'
        );

        metaTheme.setAttribute(
            'content',
            dark ? '#0c1510' : '#f4f7f5'
        );
    }

    themeBtn.addEventListener('click', function () {
        const next = root.getAttribute('data-theme') === 'dark'
            ? 'light'
            : 'dark';

        root.setAttribute('data-theme', next);

        try {
            localStorage.setItem('pos-admin-theme', next);
        } catch (e) {}

        syncTheme();
    });

    syncTheme();

    // Mobile navigation
    const menuBtn = document.getElementById('menuToggle');
    const overlay = document.getElementById('menuOverlay');

    function setNav(open) {
        body.classList.toggle('nav-open', open);

        if (menuBtn) {
            menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    if (menuBtn) {
        menuBtn.addEventListener('click', function () {
            setNav(!body.classList.contains('nav-open'));
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            setNav(false);
        });
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth > 850) {
            setNav(false);
        }
    });

    // Dashboard search
    const searchInput = document.getElementById('searchInput');

    const searchPages = [
        {
            label: 'Dashboard',
            url: @json(route('dashboard'))
        },
        @if($canManageStaff)
        {
            label: 'Staff management',
            url: @json(route('staff.index'))
        }
        @endif
    ];

    function searchPagesNow(query) {
        const term = query.trim().toLowerCase();

        if (!term) return;

        const match = searchPages.find(page =>
            page.label.toLowerCase().includes(term)
        );

        if (match) {
            window.location.href = match.url;
        }
    }

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            searchPagesNow(searchInput.value);
        }

        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.blur();
        }
    });

    // Keyboard shortcut: Ctrl + K or Cmd + K
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }

        if (e.key === 'Escape') {
            setNav(false);
        }
    });

    // Auto-dismiss flash messages
    const flash = document.querySelector('.flash');

    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity .35s ease';
            flash.style.opacity = '0';

            setTimeout(function () {
                flash.remove();
            }, 350);
        }, 6000);
    }

})();
</script>

</body>
</html>