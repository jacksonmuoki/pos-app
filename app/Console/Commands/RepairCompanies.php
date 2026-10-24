<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Throwable;

class RepairCompanies extends Command
{
    protected $signature = 'company:repair {id? : One company id, or leave empty for all}';

    protected $description = 'Check every company and create anything missing: domain, database, tables';

    public function handle(): int
    {
        $tenants = $this->argument('id')
            ? Tenant::whereKey($this->argument('id'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No companies found.');
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $id = $tenant->getTenantKey();
            $this->line("<info>$id</info>");

            try {
                // 1. Domain (the link)
                if ($tenant->domains()->count() === 0) {
                    $domain = "$id.localhost";
                    $tenant->domains()->create(['domain' => $domain]);
                    $this->line("  domain:   created $domain");
                } else {
                    $this->line('  domain:   ok (' . $tenant->domains()->pluck('domain')->implode(', ') . ')');
                }

                // 2. Database
                $db = $tenant->database()->getName();
                $exists = DB::connection(config('tenancy.database.central_connection'))
                    ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$db]);

                if (! $exists) {
                    CreateDatabase::dispatchSync($tenant);
                    $this->line("  database: created $db");
                } else {
                    $this->line("  database: ok ($db)");
                }

                // 3. Tables
                Artisan::call('tenants:migrate', ['--tenants' => [$id]]);

                if (! $tenant->run(fn () => Schema::hasTable('users'))) {
                    $this->error('  tables:   still missing. Check database/migrations/tenant has the real POS migration.');
                    continue;
                }
                $this->line('  tables:   ok');

                // 4. Staff
                $users = $tenant->run(fn () => DB::table('users')->count());
                $this->line($users ? "  users:    $users" : '  users:    none. Add one from the company page.');
            } catch (Throwable $e) {
                $this->error('  failed:   ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}