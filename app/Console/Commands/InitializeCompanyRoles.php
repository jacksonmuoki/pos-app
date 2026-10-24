<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\CompanyRoleSeeder;
use Illuminate\Console\Command;
use Throwable;

class InitializeCompanyRoles extends Command
{
    protected $signature = 'company:initialize-roles
                            {--tenant= : Initialize one company only}';

    protected $description = 'Initialize default roles and permissions for company databases';

    public function handle(CompanyRoleSeeder $roleSeeder): int
    {
        $query = Tenant::query();

        if ($tenantId = $this->option('tenant')) {
            $query->whereKey($tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error('No matching companies found.');
            return self::FAILURE;
        }

        $failed = false;

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($roleSeeder): void {
                    $roleSeeder->seed();
                });

                $this->info("Initialized roles for: {$tenant->getTenantKey()}");
            } catch (Throwable $e) {
                $failed = true;

                $this->error(
                    "Failed for {$tenant->getTenantKey()}: {$e->getMessage()}"
                );
            }
        }

        if ($failed) {
            $this->warn('Some companies could not be initialized.');
            return self::FAILURE;
        }

        $this->info('All company roles and permissions initialized.');

        return self::SUCCESS;
    }
}
