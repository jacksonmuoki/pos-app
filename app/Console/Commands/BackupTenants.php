<?php // app/Console/Commands/BackupTenants.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupTenants extends Command
{
    protected $signature = 'tenants:backup {tenant? : Company id (omit for all)}';
    protected $description = 'Back up one or all company databases';

    public function handle(BackupService $service): int
    {
        $tenants = $this->argument('tenant')
            ? Tenant::whereKey($this->argument('tenant'))->get()
            : Tenant::all();

        foreach ($tenants as $tenant) {
            $b = $service->backup($tenant);
            $this->line("{$tenant->id}: {$b->status}");
        }
        $this->info('Pruned ' . $service->prune() . ' old backup(s).');
        return self::SUCCESS;
    }
}