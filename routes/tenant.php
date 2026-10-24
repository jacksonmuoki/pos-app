<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\BrandController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\EnsureCompanyPermission;
use App\Http\Middleware\BlockSuspendedCompany;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    BlockSuspendedCompany::class,
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Company Homepage
    |--------------------------------------------------------------------------
    */

    Route::get('/', function () {

        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        $isCashier =
            strtolower((string) $user->role) === 'cashier'
            || $user->hasAnyRole(['Cashier', 'cashier'], 'web');

        if ($isCashier) {
            return redirect()->route('cashier.index');
        }

        return redirect()->route('dashboard');
    });

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/login', [LoginController::class, 'show'])
        ->name('login')
        ->middleware('guest');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware(['guest', 'throttle:5,1']);

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout')
        ->middleware('auth');

    /*
    |--------------------------------------------------------------------------
    | Authenticated Company Workspace
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Trade (Sales, Transactions, Receipts)
        |--------------------------------------------------------------------------
        */

        Route::middleware(EnsureCompanyPermission::class . ':view sales')
            ->prefix('trade')
            ->group(function () {
                Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
                Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
                Route::get('/sales/{order}', [SalesController::class, 'show'])->name('sales.show');
            });

        Route::middleware(EnsureCompanyPermission::class . ':view transactions')
            ->prefix('trade')
            ->group(function () {
                Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
                Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
            });

        Route::middleware(EnsureCompanyPermission::class . ':view receipts')
            ->prefix('trade')
            ->group(function () {
                Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
                Route::get('/receipts/{order}', [ReceiptController::class, 'show'])->name('receipts.show');
                Route::get('/receipts/{order}/download', [ReceiptController::class, 'download'])->name('receipts.download');
            });

        /*
        |--------------------------------------------------------------------------
        | Business Intelligence (Reports, Activity Logs)
        |--------------------------------------------------------------------------
        */

        Route::middleware(EnsureCompanyPermission::class . ':view reports')
            ->prefix('business')
            ->group(function () {
                Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            });

        Route::middleware(EnsureCompanyPermission::class . ':view activity logs')
            ->prefix('business')
            ->group(function () {
                Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
            });

        /*
        |--------------------------------------------------------------------------
        | Admin Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [HomeController::class, 'index'])
            ->middleware('role:admin,manager')
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Cashier POS Workspace
        |--------------------------------------------------------------------------
        */


        Route::middleware('role:admin,manager,cashier')
            ->prefix('cashier')
            ->name('cashier.')
            ->group(function () {
                Route::get('/', [CashierController::class, 'index'])->name('index');
                Route::post('/', [CashierController::class, 'act'])->name('act');
                Route::get('/payment-status', [CashierController::class, 'paymentStatus'])->name('payment-status');
            });

        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:admin,manager')->prefix('inventory')->group(function () {
            Route::get('/stock', [StockController::class, 'index'])->name('stock.index');

            Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
            Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
            Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');

            Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::patch('/suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle');

            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        });

        /*
        |--------------------------------------------------------------------------
        | Staff Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('staff')
            ->name('staff.')
            ->middleware(EnsureCompanyPermission::class . ':manage staff')
            ->controller(StaffController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{employee}/edit', 'edit')->name('edit');
                Route::put('/{employee}', 'update')->name('update');
                Route::patch('/{employee}/status', 'toggleStatus')->name('status');
                Route::delete('/{employee}', 'destroy')->name('destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Product Management
        |--------------------------------------------------------------------------
        */

        Route::middleware(EnsureCompanyPermission::class . ':manage products')->group(function () {

            Route::resource('products', ProductController::class);
            Route::resource('categories', CategoryController::class);
            Route::resource('brands', BrandController::class);

            Route::prefix('catalogue')
                ->name('catalogue.')
                ->group(function () {
                    Route::resource('products', ProductController::class);
                    Route::resource('categories', CategoryController::class);
                    Route::resource('brands', BrandController::class);
                });
        });

        /*
        |--------------------------------------------------------------------------
        | Company Settings
        |--------------------------------------------------------------------------
        */

        Route::prefix('settings')
            ->name('settings.')
            ->controller(SettingsController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::put('/', 'update')->name('update');

                Route::middleware(EnsureCompanyPermission::class . ':manage settings')->group(function () {
                    Route::post('/roles', 'storeRole')->name('roles.store');
                    Route::put('/roles/{role}', 'updateRole')->name('roles.update');
                    Route::delete('/roles/{role}', 'destroyRole')->name('roles.destroy');
                    Route::put('/staff/{user}/role', 'assignStaffRole')->name('staff.role');
                });
            });

        /*
        |--------------------------------------------------------------------------
        | Admin Area
        |--------------------------------------------------------------------------
        */

        Route::get('/admin', function () {
            return 'Admin area';
        })->middleware('role:admin,manager');

    });

});