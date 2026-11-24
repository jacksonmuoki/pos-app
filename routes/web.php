<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SuperAdmin\ActivityController;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CompanySettingsController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\LoginController;
use App\Http\Controllers\SuperAdmin\ProfileController;

use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\IntegrationController;
use App\Http\Controllers\SuperAdmin\WebhookController;
use App\Http\Controllers\SuperAdmin\HealthController;
use App\Http\Controllers\SuperAdmin\BackupController;

// New Super Admin modules
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\TransactionController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\InvoiceController;

use App\Http\Middleware\EnsureCentralDomain;
use App\Http\Middleware\SuperAuthenticate;
use App\Http\Middleware\SuperGuest;


// =====================================================
// CENTRAL DOMAIN ROOT REDIRECT
// =====================================================

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::redirect('/', '/super');
    });
}


// =====================================================
// SUPER ADMIN ROUTES
// =====================================================

Route::prefix('super')
    ->name('super.')
    ->middleware(EnsureCentralDomain::class)
    ->group(function () {

        // -------------------------------------------------
        // GUEST ROUTES
        // -------------------------------------------------

        Route::middleware(SuperGuest::class)->group(function () {

            Route::get('login', [LoginController::class, 'show'])
                ->name('login');

            Route::post('login', [LoginController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('login.store');

        });


            // =================================================
            // SUBSCRIPTIONS
            // =================================================

            Route::prefix('subscriptions')
                ->name('subscriptions.')
                ->controller(SubscriptionController::class)
                ->group(function () {

                    Route::get('/', 'index')->name('index');

                    Route::get('/create', 'create')->name('create');

                    Route::post('/', 'store')->name('store');

                    Route::get('/{subscription}', 'show')
                        ->whereNumber('subscription')
                        ->name('show');

                    Route::post('/{subscription}/cancel', 'cancel')
                        ->whereNumber('subscription')
                        ->name('cancel');
                });



        // -------------------------------------------------
        // AUTHENTICATED SUPER ADMIN ROUTES
        // -------------------------------------------------

        Route::middleware(SuperAuthenticate::class)->group(function () {

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('home', function () {
                return redirect()->route('super.dashboard');
            })->name('home');

            Route::post('logout', [LoginController::class, 'destroy'])
                ->name('logout');

            Route::post('migrate-all', [DashboardController::class, 'migrateAll'])
                ->name('migrate-all');


            // -------------------------------------------------
            // PROFILE
            // -------------------------------------------------

            Route::get('profile', [ProfileController::class, 'edit'])
                ->name('profile.edit');

            Route::patch('profile', [ProfileController::class, 'update'])
                ->name('profile.update');


            // -------------------------------------------------
            // ACTIVITY LOGS
            // -------------------------------------------------

            Route::get('activity', [ActivityController::class, 'index'])
                ->name('activity');


            // -------------------------------------------------
            // ADMIN MANAGEMENT
            // -------------------------------------------------

            Route::get('admins', [AdminController::class, 'index'])
                ->name('admins.index');

            Route::post('admins', [AdminController::class, 'store'])
                ->name('admins.store');

            Route::delete('admins/{admin}', [AdminController::class, 'destroy'])
                ->whereNumber('admin')
                ->name('admins.destroy');


            // -------------------------------------------------
            // COMPANY MANAGEMENT
            // -------------------------------------------------

            Route::get('companies', [CompanyController::class, 'index'])
                ->name('companies.index');

            Route::get('companies/create', [CompanyController::class, 'create'])
                ->name('companies.create');

            Route::post('companies', [CompanyController::class, 'store'])
                ->name('companies.store');


            Route::prefix('companies/{id}')
                ->where(['id' => '[a-z0-9]+'])
                ->group(function () {

                    Route::get('/', [CompanyController::class, 'show'])
                        ->name('companies.show');

                    Route::patch('/', [CompanyController::class, 'update'])
                        ->name('companies.update');

                    Route::delete('/', [CompanyController::class, 'destroy'])
                        ->name('companies.destroy');

                    Route::post('suspend', [CompanyController::class, 'suspend'])
                        ->name('companies.suspend');

                    Route::post('activate', [CompanyController::class, 'activate'])
                        ->name('companies.activate');

                    Route::post('migrate', [CompanyController::class, 'migrate'])
                        ->name('companies.migrate');

                    Route::post('domains', [CompanyController::class, 'addDomain'])
                        ->name('companies.domains.store');

                    Route::delete('domains/{domain}', [CompanyController::class, 'removeDomain'])
                        ->whereNumber('domain')
                        ->name('companies.domains.destroy');

                    Route::post('users', [CompanyController::class, 'addUser'])
                        ->name('companies.users.store');

                    Route::post('users/{user}/toggle', [CompanyController::class, 'toggleUser'])
                        ->whereNumber('user')
                        ->name('companies.users.toggle');

                    Route::post('users/{user}/reset-password', [CompanyController::class, 'resetPassword'])
                        ->whereNumber('user')
                        ->name('companies.users.reset');

                    Route::post('users/{user}/impersonate', [CompanyController::class, 'impersonate'])
                        ->whereNumber('user')
                        ->name('companies.users.impersonate');

                });


            // =================================================
            // PLANS & PRICING
            // =================================================

            Route::prefix('plans')->name('plans.')->group(function () {

                Route::get('/', [PlanController::class, 'index'])
                    ->name('index');

                Route::get('create', [PlanController::class, 'create'])
                    ->name('create');

                Route::post('/', [PlanController::class, 'store'])
                    ->name('store');

                Route::get('{plan}/edit', [PlanController::class, 'edit'])
                    ->name('edit');

                Route::put('{plan}', [PlanController::class, 'update'])
                    ->name('update');

                Route::patch('{plan}/toggle', [PlanController::class, 'toggle'])
                    ->name('toggle');

                Route::delete('{plan}', [PlanController::class, 'destroy'])
                    ->name('destroy');

            });


            // =================================================
            // SUBSCRIPTIONS
            // =================================================

            Route::prefix('subscriptions')->name('subscriptions.')->group(function () {

                Route::get('/', [SubscriptionController::class, 'index'])
                    ->name('index');

                Route::get('create', [SubscriptionController::class, 'create'])
                    ->name('create');

                Route::post('/', [SubscriptionController::class, 'store'])
                    ->name('store');

                Route::get('{subscription}', [SubscriptionController::class, 'show'])
                    ->name('show');

                Route::get('{subscription}/edit', [SubscriptionController::class, 'edit'])
                    ->name('edit');

                Route::put('{subscription}', [SubscriptionController::class, 'update'])
                    ->name('update');

                Route::post('{subscription}/renew', [SubscriptionController::class, 'renew'])
                    ->name('renew');

                Route::post('{subscription}/cancel', [SubscriptionController::class, 'cancel'])
                    ->name('cancel');

                Route::post('{subscription}/activate', [SubscriptionController::class, 'activate'])
                    ->name('activate');

            });


            // =================================================
            // TRANSACTIONS
            // =================================================

            Route::prefix('transactions')->name('transactions.')->group(function () {

                Route::get('/', [TransactionController::class, 'index'])
                    ->name('index');

                // Keep export before the dynamic transaction route
                Route::get('export', [TransactionController::class, 'export'])
                    ->name('export');

                Route::get('{transaction}', [TransactionController::class, 'show'])
                    ->name('show');

                Route::get('{transaction}/receipt', [TransactionController::class, 'receipt'])
                    ->name('receipt');

            });


            // =================================================
            // INVOICES & BILLING
            // =================================================

            Route::prefix('invoices')->name('invoices.')->group(function () {

                Route::get('/', [InvoiceController::class, 'index'])
                    ->name('index');

                // Keep create before the dynamic invoice route
                Route::get('create', [InvoiceController::class, 'create'])
                    ->name('create');

                Route::post('/', [InvoiceController::class, 'store'])
                    ->name('store');

                Route::get('{invoice}', [InvoiceController::class, 'show'])
                    ->name('show');

                Route::get('{invoice}/download', [InvoiceController::class, 'download'])
                    ->name('download');

                Route::get('{invoice}/print', [InvoiceController::class, 'print'])
                    ->name('print');

                Route::post('{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])
                    ->name('mark-paid');

                Route::post('{invoice}/cancel', [InvoiceController::class, 'cancel'])
                    ->name('cancel');

            });


            // =================================================
            // REPORTS
            // =================================================

            Route::prefix('reports')->name('reports.')->group(function () {

                Route::get('/', [ReportController::class, 'index'])
                    ->name('index');

                Route::get('revenue', [ReportController::class, 'revenue'])
                    ->name('revenue');

                Route::get('subscriptions', [ReportController::class, 'subscriptions'])
                    ->name('subscriptions');

                Route::get('companies', [ReportController::class, 'companies'])
                    ->name('companies');

                Route::get('transactions', [ReportController::class, 'transactions'])
                    ->name('transactions');

                Route::get('export', [ReportController::class, 'export'])
                    ->name('export');

            });


            // =================================================
            // PLATFORM SETTINGS
            // =================================================

            Route::get('settings', [SettingsController::class, 'edit'])
                ->name('settings');

            Route::put('settings', [SettingsController::class, 'update'])
                ->name('settings.update');


            // =================================================
            // EXISTING COMPANY SETTINGS, INTEGRATIONS,
            // WEBHOOKS, HEALTH AND BACKUPS
            // =================================================

            // Keep your existing routes for these modules here.
            // They were not included in the supplied web.php snippet.

        });

    });
