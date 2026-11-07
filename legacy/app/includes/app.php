<?php
define('APP_ROOT', dirname(__DIR__, 2));
define('BASE_URL', 'https://unlevelled-choric-serafina.ngrok-free.dev/pos/'); // change for your server

date_default_timezone_set('Africa/Nairobi');
error_reporting(E_ALL);
ini_set('display_errors', '0');

function legacy_url(string $area, string $page = 'dashboard'): string
{
    $base = $area === 'admin' ? 'admin' : 'cashier';
    return BASE_URL . $base . ($page === 'dashboard' ? '' : '?page=' . urlencode($page));
}