<?php
use App\Core\Route;
use App\Core\Middleware;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\CashierController;
use App\Controllers\AdminController;
use App\Controllers\PayHeroController;

// Public
Route::get('/',       [HomeController::class, 'index']);
Route::get('/login',  [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);

// Cashier and waiter
$cashier = [Middleware::role(['cashier', 'waiter'])];
Route::get('/cashier',        [CashierController::class, 'dashboard'],   $cashier);
Route::post('/cashier',       [CashierController::class, 'dashboard'],   $cashier);
Route::post('/cashier/order', [CashierController::class, 'createOrder'], $cashier);

// Cashier payment status polling (logged-in cashier/waiter only; the controller checks order ownership)
Route::get('/cashier/payment-status', [PayHeroController::class, 'status'], $cashier);

// Admin
$admin = [Middleware::role(['admin'])];
Route::get('/admin',  [AdminController::class, 'dashboard'], $admin);
Route::post('/admin', [AdminController::class, 'dashboard'], $admin);

// Print-only receipt page (admin: any receipt, cashier/waiter: own receipts)
Route::get('/receipt/print', [\App\Controllers\ReceiptController::class, 'print'],
    [Middleware::role(['admin', 'cashier', 'waiter'])]);

// PayHero callback is NOT routed here. It is served directly by public/payhero-callback.php,
// so it works without a session.
// Admin catalogue
Route::get('/admin/products',   [AdminController::class, 'products'],   $admin);
Route::post('/admin/products',  [AdminController::class, 'products'],   $admin);
Route::get('/admin/categories',  [AdminController::class, 'categories'], $admin);
Route::post('/admin/categories', [AdminController::class, 'categories'], $admin);

Route::get('/admin/inventory',  [AdminController::class, 'inventory'], $admin);
Route::post('/admin/inventory', [AdminController::class, 'inventory'], $admin);

Route::get('/admin/reports', [AdminController::class, 'reports'], $admin);

Route::get('/admin/sales', [AdminController::class, 'sales'], $admin);

Route::get('/admin/users',  [AdminController::class, 'users'], $admin);
Route::post('/admin/users', [AdminController::class, 'users'], $admin);