<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class TenantPermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            if (! function_exists('tenant') || ! tenant()) {
                return;
            }

            $tenantId = tenant('id');

            if (! $tenantId) {
                return;
            }

            config([
                'permission.cache.key' => 'spatie.permission.cache.'.$tenantId,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
