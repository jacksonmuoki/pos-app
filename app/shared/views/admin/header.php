<?php
/*
 * POS Admin Layout
 * Include before any page output.
 *
 * Optional variables:
 * $pageTitle, $activePage, $crumbs, $pageRefresh
 */

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money($v): string
    {
        return setting('currency', 'KES') . ' ' . number_format((float)$v, 2);
    }
}

if (!function_exists('pct_change')) {
    function pct_change(float $now, float $prev): ?float
    {
        if ($prev == 0.0) {
            return null;
        }

        return round((($now - $prev) / $prev) * 100, 1);
    }
}

if (!function_exists('icon')) {
    function icon(string $name): string
    {
        static $paths = [
            'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'pos' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
            'sales' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
            'reports' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
            'products' => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>',
            'categories' => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
            'inventory' => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
            'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'print' => '<path d="M6 9V3h12v6M6 18H4v-7h16v7h-2"/><rect x="6" y="14" width="12" height="7"/>',
            'alert' => '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
            'arrow' => '<path d="M5 12h14M13 5l7 7-7 7"/>',
            'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
            'close' => '<path d="M18 6L6 18M6 6l12 12"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
            'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
            'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/>',
            'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
            'keyboard' => '<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
        ];

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . ($paths[$name] ?? '') . '</svg>';
    }
}

if (!function_exists('legacy_url')) {
    function legacy_url(string $area, string $page): string
    {
        return BASE_URL . $area . '/' . $page;
    }
}

/* Navigation */
$navItems = [
    'dashboard'  => ['Dashboard', BASE_URL . 'admin', 'dashboard'],
    'pos'        => ['Point of sale', BASE_URL . 'cashier', 'pos'],
    'sales'      => ['Sales', legacy_url('admin', 'sales'), 'sales'],
    'reports'    => ['Reports', legacy_url('admin', 'reports'), 'reports'],
    'products'   => ['Products', legacy_url('admin', 'products'), 'products'],
    'categories' => ['Categories', legacy_url('admin', 'categories'), 'categories'],
    'inventory'  => ['Inventory', legacy_url('admin', 'inventory'), 'inventory'],
    'users'      => ['Users', legacy_url('admin', 'users'), 'users'],
    'settings'   => ['Settings', legacy_url('admin', 'settings'), 'settings'],
];

$navGroups = [
    'Overview'  => ['dashboard', 'pos'],
    'Trade'     => ['sales', 'reports'],
    'Catalogue' => ['products', 'categories', 'inventory'],
    'Admin'     => ['users', 'settings'],
];

$pageTitle  = $pageTitle ?? 'Admin';
$activePage = $activePage ?? '';
$crumbs     = $crumbs ?? [];

$shopName = (string)setting('shop_name', 'POS');
$user     = $_SESSION['user'] ?? [];
$userName = (string)($user['name'] ?? 'Admin');
$userRole = ucfirst((string)($user['role'] ?? 'admin'));

$parts = preg_split('/\s+/', trim($userName)) ?: ['A'];
$initials = strtoupper(
    mb_substr($parts[0], 0, 1) .
    (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '')
);

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$navBadges = ['review' => 0, 'waiting' => 0];

try {
    $pdoHdr = db();

    $r = $pdoHdr->query("
        SELECT
            SUM(status = 'review') AS review,
            SUM(status IN ('pending','unknown')
                AND payment_method = 'mpesa') AS waiting
        FROM payments
    ")->fetch();

    $navBadges['review']  = (int)($r['review'] ?? 0);
    $navBadges['waiting'] = (int)($r['waiting'] ?? 0);
} catch (Throwable $e) {
    error_log('Header badge query failed: ' . $e->getMessage());
}

$attentionCount = $navBadges['review'] + $navBadges['waiting'];
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#071b12" id="metaTheme">
<meta name="robots" content="noindex, nofollow">
<title><?= h($pageTitle) ?> · <?= h($shopName) ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script>
(function () {
    try {
        var saved = localStorage.getItem('pos-admin-theme');
        document.documentElement.setAttribute('data-theme', saved || 'dark');
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
    --shadow-sm: 0 1px 2px rgba(17, 24, 39, .04);
    --shadow: 0 1px 3px rgba(17, 24, 39, .06);
    --shadow-lg: 0 20px 40px -12px rgba(0, 0, 0, .25);
    --font: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    --sidebar-w: 250px;
    --topbar-h: 66px;
    --safe-t: env(safe-area-inset-top, 0px);
    --safe-b: env(safe-area-inset-bottom, 0px);
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
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, .3);
    --shadow: 0 1px 3px rgba(0, 0, 0, .4);
    --shadow-lg: 0 20px 40px -12px rgba(0, 0, 0, .65);
}

*, *::before, *::after {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    padding: 0;
}

html {
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
    scroll-behavior: smooth;
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
    -moz-osx-font-smoothing: grayscale;
}

a {
    color: inherit;
    text-decoration: none;
}

button, input, select, textarea {
    font: inherit;
    color: inherit;
}

button {
    cursor: pointer;
    border: 0;
    background: none;
    padding: 0;
}

button:focus-visible, a:focus-visible, input:focus-visible,
select:focus-visible, textarea:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 3px;
}

h1, h2, h3, h4 {
    margin: 0;
    font-weight: 700;
    letter-spacing: -.02em;
}

p {
    margin: 0;
}

svg {
    display: block;
    flex: none;
}

img {
    max-width: 100%;
    height: auto;
}

.num {
    font-variant-numeric: tabular-nums;
}

.muted {
    color: var(--text-3);
}

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

.mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
}

/* Main application layout */
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
    overscroll-behavior: contain;
    background: var(--surface);
    border-right: 1px solid var(--border);
    padding: 22px 14px calc(18px + var(--safe-b));
    display: flex;
    flex-direction: column;
    gap: 25px;
    z-index: 60;
}

.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: var(--border-strong);
    border-radius: 4px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 0 8px;
    min-height: 40px;
    color: var(--text);
}

.brand-mark {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    color: #fff;
    font-weight: 800;
    font-size: 16px;
    background: linear-gradient(135deg, #22c55e, #166534);
    box-shadow: 0 5px 12px rgba(22, 101, 52, .18);
    flex: none;
}

.brand-text {
    min-width: 0;
}

.brand-name {
    display: block;
    font-weight: 750;
    font-size: 15px;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.brand-tag {
    display: block;
    font-size: 10px;
    color: var(--text-3);
    letter-spacing: .09em;
    text-transform: uppercase;
    margin-top: 3px;
}

.nav {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.nav-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--text-muted);
    padding: 0 11px 7px;
}

.nav-group {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 11px;
    border-radius: 9px;
    color: var(--text-2);
    font-weight: 500;
    font-size: 13px;
    transition: background .16s ease, color .16s ease;
    min-height: 42px;
}

.nav-link svg {
    width: 17px;
    height: 17px;
}

.nav-link:hover {
    background: var(--surface-2);
    color: var(--text);
}

.nav-link.is-active {
    background: var(--primary-soft);
    color: var(--primary);
    font-weight: 650;
}

.nav-link.is-active::before {
    content: "";
    position: absolute;
    left: 0;
    top: 9px;
    bottom: 9px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--primary);
}

.nav-text {
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nav-count {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    background: var(--danger);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: grid;
    place-items: center;
    font-variant-numeric: tabular-nums;
}

.nav-count.is-warn {
    background: var(--warning);
    color: #fff;
}

.sidebar-foot {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 13px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

.me {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 4px;
    min-width: 0;
}

.avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: #fff;
    font-weight: 700;
    font-size: 12px;
    background: linear-gradient(135deg, #22c55e, #166534);
    flex: none;
}

.me-text {
    min-width: 0;
}

.me-name {
    font-weight: 600;
    font-size: 12.5px;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.me-role {
    font-size: 11.5px;
    color: var(--text-3);
    margin-top: 2px;
}

.main {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/* Top navigation */
.topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: var(--topbar-h);
    padding: 12px 28px;
    padding-top: calc(12px + var(--safe-t));
    background: color-mix(in srgb, var(--surface) 94%, transparent);
    backdrop-filter: saturate(1.4) blur(12px);
    -webkit-backdrop-filter: saturate(1.4) blur(12px);
    border-bottom: 1px solid var(--border);
}

.crumbs {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 12.5px;
    color: var(--text-3);
    min-width: 0;
    overflow: hidden;
}

.crumbs a {
    white-space: nowrap;
}

.crumbs a:hover {
    color: var(--primary);
}

.crumbs strong {
    color: var(--text);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.crumbs .sep {
    color: var(--text-muted);
}

.spacer {
    flex: 1;
}

.search {
    position: relative;
    width: 290px;
    max-width: 100%;
}

.search-field {
    display: flex;
    align-items: center;
    gap: 9px;
    height: 39px;
    padding: 0 11px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--surface-2);
    color: var(--text-3);
}

.search-field:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}

.search-field svg {
    width: 16px;
    height: 16px;
}

.search-field input {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    outline: none;
    color: var(--text);
    font-size: 12.5px;
}

.search-field input::placeholder {
    color: var(--text-3);
}

.search-field kbd {
    font-family: ui-monospace, monospace;
    font-size: 10px;
    padding: 2px 5px;
    border: 1px solid var(--border);
    border-radius: 5px;
    background: var(--surface);
    color: var(--text-3);
}

.search-results {
    position: absolute;
    top: calc(100% + 7px);
    left: 0;
    right: 0;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    padding: 6px;
    display: none;
    max-height: 360px;
    overflow-y: auto;
    z-index: 70;
}

.search.is-open .search-results {
    display: block;
}

.search-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 8px;
    font-size: 13px;
    color: var(--text-2);
}

.search-item:hover,
.search-item.is-active {
    background: var(--surface-2);
    color: var(--text);
}

.search-item svg {
    width: 16px;
    height: 16px;
    color: var(--text-3);
}

.search-empty {
    padding: 12px;
    color: var(--text-3);
    font-size: 13px;
    text-align: center;
}

.icon-btn {
    position: relative;
    width: 39px;
    height: 39px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    color: var(--text-2);
    border: 1px solid var(--border);
    background: var(--surface);
    transition: background .15s ease, color .15s ease;
    flex: none;
}

.icon-btn:hover {
    background: var(--surface-2);
    color: var(--primary);
}

.icon-btn svg {
    width: 17px;
    height: 17px;
}

.icon-dot {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: var(--danger);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    display: grid;
    place-items: center;
    border: 2px solid var(--surface);
    font-variant-numeric: tabular-nums;
}

.icon-dot.is-warn {
    background: var(--warning);
}

.menu-btn,
.search-mobile-btn {
    display: none;
}

.menu-overlay {
    display: none;
}

.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 320px;
    max-width: calc(100vw - 24px);
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 80;
}

.dropdown.is-open .dropdown-menu {
    display: flex;
}

.dd-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 13px 14px;
    border-bottom: 1px solid var(--border);
    font-weight: 600;
    font-size: 13px;
}

.dd-body {
    max-height: 320px;
    overflow-y: auto;
}

.dd-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    font-size: 12.5px;
    color: var(--text-2);
}

.dd-item:last-child {
    border-bottom: 0;
}

.dd-item:hover {
    background: var(--surface-2);
}

.dd-item strong {
    color: var(--text);
    font-weight: 600;
    display: block;
}

.dd-item small {
    color: var(--text-3);
    font-size: 11.5px;
    display: block;
    margin-top: 2px;
}

.dd-foot {
    padding: 11px 14px;
    border-top: 1px solid var(--border);
    text-align: center;
}

.dd-foot a {
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
}

.dd-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-top: 5px;
    flex: none;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 3px 10px 3px 3px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: var(--surface);
}

.user-btn:hover {
    background: var(--surface-2);
}

.user-btn .avatar {
    width: 31px;
    height: 31px;
}

.user-btn-name {
    font-size: 12px;
    font-weight: 600;
    max-width: 125px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-btn svg {
    width: 14px;
    height: 14px;
    color: var(--text-3);
}

.user-menu {
    width: 230px;
}

.user-menu .dd-item {
    font-size: 13px;
    align-items: center;
}

.user-menu .dd-item svg {
    width: 16px;
    height: 16px;
    color: var(--text-3);
}

/* Page content */
.content {
    flex: 1;
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 0;
}

.flash {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: var(--radius);
    font-weight: 500;
    border: 1px solid;
    font-size: 13px;
}

.flash--success {
    background: var(--success-soft);
    color: var(--success);
    border-color: var(--success);
}

.flash--error {
    background: var(--danger-soft);
    color: var(--danger);
    border-color: var(--danger);
}

.flash--info {
    background: var(--info-soft);
    color: var(--info);
    border-color: var(--info);
}

.flash--warning {
    background: var(--warning-soft);
    color: var(--warning);
    border-color: var(--warning);
}

/* Reusable page components */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 39px;
    padding: 9px 14px;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 600;
    border: 1px solid transparent;
    white-space: nowrap;
    transition: background .15s ease, border-color .15s ease, color .15s ease;
    cursor: pointer;
    text-decoration: none;
}

.btn svg {
    width: 15px;
    height: 15px;
}

.btn--primary {
    background: var(--primary);
    color: #fff;
    box-shadow: var(--shadow-sm);
}

.btn--primary:hover {
    background: var(--primary-hover);
}

[data-theme="dark"] .btn--primary {
    color: #06240f;
}

.btn--ghost {
    background: var(--surface);
    color: var(--text-2);
    border-color: var(--border);
}

.btn--ghost:hover {
    background: var(--surface-2);
    color: var(--text);
    border-color: var(--border-strong);
}

.btn--danger {
    background: var(--danger-soft);
    color: var(--danger);
    border-color: var(--danger);
}

.btn--block {
    width: 100%;
}

.btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 21px;
    box-shadow: var(--shadow-sm);
    min-width: 0;
    overflow-wrap: anywhere;
}

.page-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.eyebrow {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--text-3);
}

.page-title {
    font-size: 27px;
    line-height: 1.25;
    margin-top: 4px;
    letter-spacing: -.035em;
}

.page-sub {
    color: var(--text-2);
    font-size: 13px;
    margin-top: 5px;
}

.page-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 650;
    white-space: nowrap;
}

.badge--ok {
    background: var(--success-soft);
    color: var(--success);
}

.badge--warn {
    background: var(--warning-soft);
    color: var(--warning);
}

.badge--bad {
    background: var(--danger-soft);
    color: var(--danger);
}

.badge--review {
    background: var(--purple-soft);
    color: var(--purple);
}

.badge--muted {
    background: var(--surface-2);
    color: var(--text-3);
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--border);
    border-radius: 10px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.table th {
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--text-3);
    padding: 12px 11px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
    white-space: nowrap;
}

.table td {
    padding: 13px 11px;
    border-bottom: 1px solid var(--border);
    color: var(--text-2);
    white-space: nowrap;
}

.table tr:last-child td {
    border-bottom: 0;
}

.table tbody tr:hover td {
    background: var(--surface-2);
}

.table .r {
    text-align: right;
}

.empty {
    padding: 30px 10px;
    text-align: center;
    color: var(--text-3);
    font-size: 13px;
}

/* Responsive layout */
@media (max-width: 1280px) {
    .content {
        padding: 24px 22px;
    }

    .topbar {
        padding-left: 22px;
        padding-right: 22px;
    }

    .search {
        width: 240px;
    }
}

@media (max-width: 1024px) {
    .shell {
        grid-template-columns: minmax(0, 1fr);
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        height: 100vh;
        height: 100dvh;
        width: min(285px, 86vw);
        transform: translateX(-105%);
        visibility: hidden;
        transition: transform .24s ease, visibility .24s ease;
        box-shadow: var(--shadow-lg);
        padding-top: calc(22px + var(--safe-t));
        z-index: 100;
    }

    body.nav-open .sidebar {
        transform: translateX(0);
        visibility: visible;
    }

    .menu-btn {
        display: grid;
    }

    .menu-overlay {
        position: fixed;
        inset: 0;
        z-index: 90;
        background: rgba(2, 12, 6, .58);
        backdrop-filter: blur(2px);
        opacity: 0;
        visibility: hidden;
        transition: opacity .24s ease, visibility .24s ease;
    }

    body.nav-open .menu-overlay {
        opacity: 1;
        visibility: visible;
        display: block;
    }

    body.nav-open {
        overflow: hidden;
    }

    .search {
        width: 230px;
    }

    .user-btn-name {
        display: none;
    }

    .user-btn {
        padding: 3px;
    }

    .user-btn > svg {
        display: none;
    }
}

@media (max-width: 768px) {
    :root {
        --topbar-h: 59px;
    }

    .topbar {
        gap: 8px;
        padding: 8px 14px;
        padding-top: calc(8px + var(--safe-t));
        min-height: var(--topbar-h);
    }

    .crumbs {
        display: none;
    }

    .search {
        flex: 1;
        width: auto;
        min-width: 0;
    }

    .search-field kbd {
        display: none;
    }

    .content {
        padding: 19px 14px calc(25px + var(--safe-b));
        gap: 16px;
    }

    .page-title {
        font-size: 23px;
    }

    .flash {
        font-size: 12.5px;
    }

    .card {
        padding: 17px;
        border-radius: 13px;
    }

    .page-head {
        align-items: flex-start;
    }

    .page-actions {
        width: 100%;
    }

    .page-actions .btn {
        flex: 1;
    }

    .table {
        font-size: 12px;
    }

    .table th,
    .table td {
        padding: 10px 8px;
    }
}

@media (max-width: 480px) {
    .topbar {
        gap: 6px;
        padding-left: 10px;
        padding-right: 10px;
    }

    .search {
        display: none;
    }

    .search-mobile-btn {
        display: grid;
    }

    body.search-open .search {
        display: block;
        position: fixed;
        left: 10px;
        right: 10px;
        top: calc(8px + var(--safe-t));
        width: auto;
        z-index: 110;
    }

    body.search-open .search-field {
        box-shadow: var(--shadow-lg);
        background: var(--surface);
    }

    .content {
        padding: 16px 12px calc(20px + var(--safe-b));
    }

    .icon-btn {
        width: 36px;
        height: 36px;
    }

    .dropdown-menu {
        width: min(320px, calc(100vw - 20px));
        right: -45px;
    }

    .btn {
        min-height: 41px;
    }

    .table th,
    .table td {
        padding: 9px 7px;
    }

    .page-title {
        font-size: 22px;
    }
}

@media (max-width: 380px) {
    .content {
        padding-left: 10px;
        padding-right: 10px;
    }

    .topbar {
        padding-left: 8px;
        padding-right: 8px;
    }

    .page-title {
        font-size: 21px;
    }

    .icon-btn {
        width: 34px;
        height: 34px;
    }

    .card {
        padding: 14px;
    }
}

@media (pointer: coarse) {
    .nav-link {
        min-height: 44px;
    }

    .icon-btn {
        width: 42px;
        height: 42px;
    }

    .dd-item {
        padding: 13px 14px;
    }
}

@media (max-height: 500px) and (orientation: landscape) {
    .sidebar {
        padding-top: 10px;
        gap: 12px;
    }

    .nav {
        gap: 10px;
    }

    .nav-label {
        padding-bottom: 2px;
    }

    .topbar {
        min-height: 52px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition: none !important;
        animation: none !important;
    }
}

::selection {
    background: var(--primary-soft);
    color: var(--text);
}

.card,
.flash,
.btn,
.icon-btn,
.nav-link {
    -webkit-tap-highlight-color: transparent;
}

input,
select,
textarea {
    max-width: 100%;
}

@media print {
    .sidebar,
    .topbar,
    .footer,
    .page-actions,
    .menu-overlay,
    .flash {
        display: none !important;
    }

    .shell {
        display: block;
    }

    .content {
        padding: 0;
        max-width: none;
    }

    .card {
        box-shadow: none;
        border-color: #ccc;
        break-inside: avoid;
    }

    body {
        background: #fff;
        color: #000;
    }
}
</style>
</head>

<body data-page="<?= h($activePage) ?>">
<div class="shell">

    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <a class="brand" href="<?= h(BASE_URL . 'admin') ?>">
            <span class="brand-mark" aria-hidden="true"><?= h(mb_substr($shopName, 0, 1)) ?></span>
            <span class="brand-text">
                <span class="brand-name"><?= h($shopName) ?></span>
                <span class="brand-tag">Point of sale</span>
            </span>
        </a>

        <nav class="nav" aria-label="Primary">
            <?php foreach ($navGroups as $group => $keys): ?>
                <div class="nav-group">
                    <div class="nav-label"><?= h($group) ?></div>

                    <?php foreach ($keys as $key):
                        [$label, $url, $ic] = $navItems[$key];
                        $on = $activePage === $key;
                        $count = 0;

                        if ($key === 'pos') {
                            $count = $navBadges['waiting'];
                        }

                        if ($key === 'sales') {
                            $count = $navBadges['review'];
                        }
                    ?>
                        <a class="nav-link<?= $on ? ' is-active' : '' ?>"
                           href="<?= h($url) ?>"
                           <?= $on ? 'aria-current="page"' : '' ?>>
                            <?= icon($ic) ?>
                            <span class="nav-text"><?= h($label) ?></span>

                            <?php if ($count > 0): ?>
                                <span class="nav-count<?= $key === 'pos' ? ' is-warn' : '' ?>"
                                      aria-label="<?= (int)$count ?> need attention">
                                    <?= (int)$count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-foot">
            <div class="me">
                <div class="avatar" aria-hidden="true"><?= h($initials) ?></div>
                <div class="me-text">
                    <div class="me-name"><?= h($userName) ?></div>
                    <div class="me-role"><?= h($userRole) ?></div>
                </div>
            </div>

            <a class="btn btn--ghost btn--block" href="<?= h(BASE_URL . 'logout') ?>">
                <?= icon('logout') ?> Sign out
            </a>
        </div>
    </aside>

    <div class="menu-overlay" id="menuOverlay" aria-hidden="true"></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-btn"
                    type="button"
                    id="menuToggle"
                    aria-label="Open menu"
                    aria-controls="sidebar"
                    aria-expanded="false">
                <?= icon('menu') ?>
            </button>

            <nav class="crumbs" aria-label="Breadcrumb">
                <?php
                $crumbIndex = 0;
                foreach ($crumbs as $label => $url):
                    $crumbIndex++;
                ?>
                    <?php if ($crumbIndex > 1): ?>
                        <span class="sep">/</span>
                    <?php endif; ?>

                    <?php if ($url): ?>
                        <a href="<?= h($url) ?>"><?= h($label) ?></a>
                    <?php else: ?>
                        <strong><?= h($label) ?></strong>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="spacer"></div>

            <button class="icon-btn search-mobile-btn"
                    type="button"
                    id="searchMobileBtn"
                    aria-label="Search">
                <?= icon('search') ?>
            </button>

            <div class="search" id="globalSearch" role="search">
                <label class="search-field">
                    <?= icon('search') ?>
                    <span class="sr-only">Search</span>
                    <input type="search"
                           id="searchInput"
                           placeholder="Search orders, products, staff…"
                           autocomplete="off"
                           aria-controls="searchResults"
                           aria-expanded="false">
                    <kbd>⌘K</kbd>
                </label>
                <div class="search-results" id="searchResults" role="listbox"></div>
            </div>

            <div class="dropdown" id="notifyDd">
                <button class="icon-btn"
                        type="button"
                        aria-label="Notifications (<?= (int)$attentionCount ?> need attention)"
                        aria-haspopup="true"
                        aria-expanded="false">
                    <?= icon('bell') ?>

                    <?php if ($attentionCount > 0): ?>
                        <span class="icon-dot">
                            <?= $attentionCount > 99 ? '99+' : (int)$attentionCount ?>
                        </span>
                    <?php endif; ?>
                </button>

                <div class="dropdown-menu" role="menu">
                    <div class="dd-head">
                        <span>Notifications</span>
                        <span class="muted"><?= (int)$attentionCount ?> new</span>
                    </div>

                    <div class="dd-body">
                        <?php if ($navBadges['review'] > 0): ?>
                            <a class="dd-item" href="<?= h(legacy_url('admin', 'sales')) ?>">
                                <span class="dd-dot" style="background:var(--danger)"></span>
                                <div>
                                    <strong><?= (int)$navBadges['review'] ?> payment(s) need review</strong>
                                    <small>Check these before closing the day</small>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if ($navBadges['waiting'] > 0): ?>
                            <a class="dd-item" href="<?= h(BASE_URL . 'cashier') ?>">
                                <span class="dd-dot" style="background:var(--warning)"></span>
                                <div>
                                    <strong><?= (int)$navBadges['waiting'] ?> M-Pesa prompt(s) waiting</strong>
                                    <small>Waiting for customer PIN</small>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if ($attentionCount === 0): ?>
                            <div class="dd-item">
                                <span class="dd-dot" style="background:var(--success)"></span>
                                <div>
                                    <strong>All clear</strong>
                                    <small>Nothing needs attention right now</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dd-foot">
                        <a href="<?= h(legacy_url('admin', 'sales')) ?>">View all sales</a>
                    </div>
                </div>
            </div>

            <button class="icon-btn"
                    type="button"
                    id="themeToggle"
                    aria-label="Switch to light mode"
                    title="Switch theme">
                <span data-theme-icon="dark"><?= icon('sun') ?></span>
                <span data-theme-icon="light" hidden><?= icon('moon') ?></span>
            </button>

            <button class="icon-btn"
                    type="button"
                    id="kbdHelpBtn"
                    aria-label="Keyboard shortcuts"
                    title="Keyboard shortcuts"
                    style="display:none"
                    data-desktop-only>
                <?= icon('keyboard') ?>
            </button>

            <div class="dropdown" id="userDd">
                <button class="user-btn"
                        type="button"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="Account menu for <?= h($userName) ?>">
                    <span class="avatar" aria-hidden="true"><?= h($initials) ?></span>
                    <span class="user-btn-name"><?= h($userName) ?></span>
                    <svg viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         aria-hidden="true">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>

                <div class="dropdown-menu user-menu" role="menu">
                    <div class="dd-head">
                        <span><?= h($userName) ?></span>
                        <span class="muted"><?= h($userRole) ?></span>
                    </div>

                    <a class="dd-item"
                       href="<?= h(legacy_url('admin', 'settings')) ?>"
                       role="menuitem">
                        <?= icon('settings') ?> Settings
                    </a>

                    <a class="dd-item"
                       href="<?= h(BASE_URL . 'cashier') ?>"
                       role="menuitem">
                        <?= icon('pos') ?> Open POS
                    </a>

                    <a class="dd-item"
                       href="<?= h(BASE_URL . 'logout') ?>"
                       role="menuitem">
                        <?= icon('logout') ?> Sign out
                    </a>
                </div>
            </div>
        </header>

        <main class="content" id="main">
            <?php if ($flash): ?>
                <div class="flash flash--<?= h($flash['type'] ?? 'success') ?>"
                     role="status">
                    <?= h($flash['msg'] ?? '') ?>
                </div>
            <?php endif; ?>