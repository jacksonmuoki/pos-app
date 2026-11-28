<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#14532d">

    @php
        // Company business name (set in Settings), falling back to the app name.
        try {
            $shopName = \App\Support\CompanySettings::get('business_name') ?: config('app.name', 'POS');
        } catch (\Throwable $e) {
            $shopName = config('app.name', 'POS');
        }
    @endphp

    <title>@yield('title', 'Dashboard') | {{ $shopName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 Free -->
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>

    <style>
        :root {
            --primary: #15803d;
            --primary-dark: #14532d;
            --primary-light: #dcfce7;
            --bg: #f0fdf4;
            --surface: #ffffff;
            --surface-soft: #f8faf9;
            --text: #17251c;
            --muted: #64746a;
            --border: #e2e8e4;
            --danger: #dc2626;
            --sidebar-width: 255px;
            --topbar-height: 68px;
            --radius: 14px;
            --shadow: 0 5px 20px rgba(15, 50, 28, .06);
        }

        [data-theme="dark"] {
            --bg: #101914;
            --surface: #17231b;
            --surface-soft: #1d2b22;
            --text: #e8f1eb;
            --muted: #9aada0;
            --border: #304236;
            --primary-light: #193c27;
            --shadow: 0 5px 20px rgba(0, 0, 0, .18);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, sans-serif;
            font-size: 14px;
        }

        a { color: inherit; text-decoration: none; }
        button, input, textarea, select { font: inherit; }

        .app-shell { min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-width);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform .25s ease;
        }
        .sidebar a i {
    width: 22px;
    min-width: 22px;
    font-size: 16px;
    margin-right: 12px;
    text-align: center;
}

        .brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 22px;
            border-bottom: 1px solid var(--border);
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            background: var(--primary);
            color: white;
            border-radius: 11px;
            font-size: 18px;
            font-weight: 800;
        }

        .brand-name { font-size: 17px; font-weight: 800; letter-spacing: -.5px; }

        .brand-caption {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 3px;
        }

        .sidebar-content { flex: 1; overflow-y: auto; padding: 18px 13px; }

        .nav-section { margin-bottom: 24px; }

        .nav-heading {
            padding: 0 11px;
            margin-bottom: 9px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.1px;
            text-transform: uppercase;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            padding: 0 12px;
            margin: 3px 0;
            border-radius: 9px;
            color: var(--muted);
            font-weight: 600;
            transition: .2s;
        }

        .nav-link:hover { background: var(--surface-soft); color: var(--primary); }

        .nav-link.active {
            background: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 700;
        }

        [data-theme="dark"] .nav-link.active { color: #86efac; }

        .nav-icon { width: 21px; text-align: center; font-size: 16px; }

        .sidebar-bottom { padding: 15px; border-top: 1px solid var(--border); }

        .company-card {
            padding: 12px;
            background: var(--surface-soft);
            border: 1px solid var(--border);
            border-radius: 11px;
        }

        .company-card strong { display: block; font-size: 12px; margin-bottom: 4px; }
        .company-card small { color: var(--muted); }

        /* MAIN AREA */
        .main-wrapper { min-height: 100vh; margin-left: var(--sidebar-width); }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 0 28px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 13px; }

        .page-context { font-size: 13px; font-weight: 700; }

        .topbar-search {
            width: 260px;
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--surface-soft);
            color: var(--text);
            outline: none;
        }

        .topbar-search:focus { border-color: var(--primary); }

        .icon-button {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--surface);
            color: var(--text);
            cursor: pointer;
        }

        .icon-button:hover { background: var(--surface-soft); }

        .user-menu { display: flex; align-items: center; gap: 9px; }

        .user-avatar {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 800;
        }

        [data-theme="dark"] .user-avatar { color: #86efac; }

        .user-details strong { display: block; font-size: 12px; }
        .user-details small { display: block; color: var(--muted); margin-top: 3px; }

        .mobile-menu { display: none; }

        .content {
            max-width: 1600px;
            padding: 28px;
            margin: auto;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ALERTS */
        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .alert-success { color: #166534; background: #dcfce7; border: 1px solid #bbf7d0; }
        .alert-danger  { color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; }

        [data-theme="dark"] .alert-success { color: #bbf7d0; background: #193c27; border-color: #285c3a; }
        [data-theme="dark"] .alert-danger  { color: #fecaca; background: #451c1c; border-color: #713333; }

        /* FORMS AND CARDS (used by settings pages) */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 22px;
            margin-bottom: 20px;
        }

        .card input:not([type=checkbox]):not([type=radio]),
        .card textarea,
        .card select {
            background: var(--surface-soft);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .card button[type=submit] {
            background: var(--primary);
            color: #fff;
            border: 0;
            border-radius: 9px;
            padding: 10px 18px;
            font-weight: 700;
            cursor: pointer;
        }

        .card button[type=submit]:hover { background: var(--primary-dark); }

        /* FOOTER */
        .app-footer {
            padding: 18px 28px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .sidebar-overlay { display: none; }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .mobile-menu { display: grid; }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 90;
                background: rgba(0, 0, 0, .45);
            }

            .content { padding: 20px; }
        }

        @media (max-width: 600px) {
            .topbar { padding: 0 14px; }
            .topbar-search, .user-details, .page-context { display: none; }
            .content { padding: 16px; }
            .app-footer { padding: 16px; }
        }
    </style>

    @stack('styles')
</head>

<body>
@php
    $currentUser = auth()->user();

    $userName = $currentUser->name ?? 'User';
    $userRole = ucfirst($currentUser->role ?? 'Staff');

    $initials = collect(explode(' ', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');

    $canManageStaff = $currentUser
        && method_exists($currentUser, 'canManageStaff')
        && $currentUser->canManageStaff();
@endphp

<div class="app-shell">

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">

        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">{{ strtoupper(substr($shopName, 0, 1)) }}</span>
            <span>
                <span class="brand-name">{{ $shopName }}</span>
                <span class="brand-caption">BUSINESS MANAGEMENT</span>
            </span>
        </a>

        <div class="sidebar-content">

            <div class="nav-section">
                <div class="nav-heading">Overview</div>

                <a href="{{ route('dashboard') }}"
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span> Dashboard
                </a>

                <a href="{{ route('cashier.index') }}"
                   class="nav-link {{ request()->routeIs('cashier.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-cash-register"></i></span> Point of Sale
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-heading">Trade</div>

                <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span> Sales
                </a>
                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-exchange-alt"></i></span> Transactions
                </a>
                <a href="{{ route('receipts.index') }}" class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-receipt"></i></span> Receipts
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-heading">Catalogue</div>

                <a href="{{ route('products.create') }}" class="nav-link {{ request()->routeIs('products.create') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-plus"></i></span> Add Product
                </a>
                <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index', 'products.edit', 'products.show') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-box-open"></i></span> Products
                </a>
                <a href="{{ route('categories.create') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-list"></i></span> Categories
                </a>
                <a href="{{ route('brands.create') }}" class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-tag"></i></span> Brands
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-heading">Inventory</div>

                <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-boxes-stacked"></i></span> Stock Management
                </a>
                <a href="{{ route('adjustments.index') }}" class="nav-link {{ request()->routeIs('adjustments.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-exchange-alt"></i></span> Stock Adjustments
                </a>
                <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-truck"></i></span> Suppliers
                </a>
                <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-shopping-bag"></i></span> Purchases
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-heading">Finance</div>

                <a href="{{ route('dashboard') }}" class="nav-link">
                    <span class="nav-icon"><i class="fa-regular fa-money-bill-transfer"></i></span> Payments
                </a>
                <a href="{{ route('dashboard') }}" class="nav-link">
                    <span class="nav-icon"><i class="fa-solid fa-money-bill-wave"></i></span> Expenses
                </a>
                <a href="{{ route('dashboard') }}" class="nav-link">
                    <span class="nav-icon"><i class="fa-solid fa-coins"></i></span> Cash Management
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-heading">Business Intelligence</div>

                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span> Reports
                </a>
                <a href="{{ route('activity.index') }}" class="nav-link {{ request()->routeIs('activity.*') ? 'active' : '' }}">
                    <span class="nav-icon"><i class="fa-solid fa-clock"></i></span> Activity Logs
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-heading">Administration</div>

                @if($canManageStaff && \Illuminate\Support\Facades\Route::has('staff.index'))
                    <a href="{{ route('staff.index') }}"
                       class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-user-friends"></i></span> Staff Management
                    </a>
                @endif

                @can('manage settings')
                    <a href="{{ route('settings.edit') }}"
                       class="nav-link {{ request()->routeIs('settings.edit', 'settings.update') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-cog"></i></span> Company Settings
                    </a>

                    <a href="{{ route('settings.roles.index') }}"
                       class="nav-link {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}">
                        <span class="nav-icon"><i class="fa-solid fa-chess-king"></i></span> Roles &amp; Permissions
                    </a>
                @endcan

                <a href="{{ route('dashboard') }}" class="nav-link">
                    <span class="nav-icon"><i class="fa-solid fa-building"></i></span> Branches
                </a>
            </div>

        </div>

        <div class="sidebar-bottom">
            <div class="company-card">
                <strong>{{ $shopName }}</strong>
                <small>Company workspace</small>
            </div>
        </div>

    </aside>

    <div class="main-wrapper">

        <header class="topbar">

            <div class="topbar-left">
                <button class="icon-button mobile-menu" id="mobileMenu" type="button" aria-label="Open navigation">☰</button>
                <span class="page-context">@yield('page-heading', 'Company Workspace')</span>
            </div>

            <div class="topbar-right">

                <input type="search" class="topbar-search" id="globalSearch"
                       placeholder="Search workspace... (Ctrl + K)">

                <button class="icon-button" id="themeToggle" type="button" title="Toggle theme">◐</button>

                <div class="user-menu">
                    <div class="user-avatar">{{ $initials ?: 'U' }}</div>

                    <div class="user-details">
                        <strong>{{ $userName }}</strong>
                        <small>{{ $userRole }}</small>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="icon-button" type="submit" title="Logout">⇥</button>
                </form>

            </div>

        </header>

        <main class="content" id="main">

            @if(session('success'))
                <div class="alert alert-success flash">{{ session('success') }}</div>
            @endif

            @if(session('status'))
                <div class="alert alert-success flash">{{ session('status') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger flash">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')

        </main>

        <footer class="app-footer">
            <span>© {{ date('Y') }} {{ $shopName }}. All rights reserved.</span>
            <span>Business Management System</span>
        </footer>

    </div>
</div>

<script>
(() => {
    const root = document.documentElement;
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuButton = document.getElementById('mobileMenu');
    const themeButton = document.getElementById('themeToggle');
    const searchInput = document.getElementById('globalSearch');

    // Theme
    let savedTheme = 'light';
    try { savedTheme = localStorage.getItem('pos-admin-theme') || 'light'; } catch (e) {}
    root.setAttribute('data-theme', savedTheme);

    themeButton?.addEventListener('click', () => {
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('pos-admin-theme', next); } catch (e) {}
    });

    // Mobile navigation
    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('show');
    }

    menuButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        overlay?.classList.toggle('show');
    });

    overlay?.addEventListener('click', closeSidebar);

    // Global search shortcut
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            searchInput?.focus();
        }

        if (event.key === 'Escape') {
            closeSidebar();
            searchInput?.blur();
        }
    });

    // Flash messages fade out
    document.querySelectorAll('.flash').forEach(flash => {
        setTimeout(() => {
            flash.style.transition = 'opacity .3s ease';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 350);
        }, 4500);
    });
})();
</script>

@stack('scripts')

</body>
</html>