<?php

use App\Http\Controllers\SuperAdmin\ActivityController;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CompanySettingsController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\LoginController;
use App\Http\Controllers\SuperAdmin\ProfileController;
use App\Http\Controllers\SuperAdmin\{SettingsController, IntegrationController, WebhookController, HealthController, BackupController};

use App\Http\Middleware\EnsureCentralDomain;
use App\Http\Middleware\SuperAuthenticate;
use App\Http\Middleware\SuperGuest;
use Illuminate\Support\Facades\Route;

// The bare central address goes to the platform console.
// Company domains are not affected: they use routes/tenant.php.
foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::redirect('/', '/super');
    });
}

Route::prefix('super')->name('super.')->middleware(EnsureCentralDomain::class)->group(function () {

    Route::middleware(SuperGuest::class)->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware(SuperAuthenticate::class)->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('migrate-all', [DashboardController::class, 'migrateAll'])->name('migrate-all');

        Route::get('activity', [ActivityController::class, 'index'])->name('activity');

        Route::get('home', fn () => redirect()->route('super.dashboard'))->name('home');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        // Admin management
        Route::get('admins', [AdminController::class, 'index'])->name('admins.index');
        Route::post('admins', [AdminController::class, 'store'])->name('admins.store');
        Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->whereNumber('admin')->name('admins.destroy');

        // Company management
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');

        Route::prefix('companies/{id}')->where(['id' => '[a-z0-9]+'])->group(function () {
            Route::get('/', [CompanyController::class, 'show'])->name('companies.show');
            Route::patch('/', [CompanyController::class, 'update'])->name('companies.update');
            Route::delete('/', [CompanyController::class, 'destroy'])->name('companies.destroy');
            Route::post('suspend', [CompanyController::class, 'suspend'])->name('companies.suspend');
            Route::post('activate', [CompanyController::class, 'activate'])->name('companies.activate');
            Route::post('migrate', [CompanyController::class, 'migrate'])->name('companies.migrate');

            Route::post('domains', [CompanyController::class, 'addDomain'])->name('companies.domains.store');
            Route::delete('domains/{domain}', [CompanyController::class, 'removeDomain'])->whereNumber('domain')->name('companies.domains.destroy');

            Route::post('users', [CompanyController::class, 'addUser'])->name('companies.users.store');
            Route::post('users/{user}/toggle', [CompanyController::class, 'toggleUser'])->whereNumber('user')->name('companies.users.toggle');
            Route::post('users/{user}/reset-password', [CompanyController::class, 'resetPassword'])->whereNumber('user')->name('companies.users.reset');
            Route::post('users/{user}/impersonate', [CompanyController::class, 'impersonate'])->whereNumber('user')->name('companies.users.impersonate');
        });

        // Platform settings
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        // Per-company settings (currency, payments, SMS, maintenance)
        Route::prefix('company-settings/{company}')->where(['company' => '[a-z0-9]+'])->group(function () {
            Route::get('/', [CompanySettingsController::class, 'edit'])->name('company-settings.edit');
            Route::put('/', [CompanySettingsController::class, 'update'])->name('company-settings.update');
        });

        // Integrations
        Route::get('integrations', [IntegrationController::class, 'index'])->name('integrations');
        Route::put('integrations/{provider}', [IntegrationController::class, 'update'])->name('integrations.update');

        // Webhooks
        Route::get('webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
        Route::post('webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
        Route::post('webhooks/{webhook}/toggle', [WebhookController::class, 'toggle'])->name('webhooks.toggle');
        Route::post('webhooks/{webhook}/test', [WebhookController::class, 'test'])->middleware('throttle:10,1')->name('webhooks.test');
        Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
        Route::post('webhooks/deliveries/{delivery}/resend', [WebhookController::class, 'resend'])->name('webhooks.resend');

        // System health
        Route::get('health', [HealthController::class, 'index'])->name('health');

        // Backups
        Route::get('backups', [BackupController::class, 'index'])->name('backups');
        Route::post('backups', [BackupController::class, 'store'])->middleware('throttle:10,1')->name('backups.store');
        Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
    });
});