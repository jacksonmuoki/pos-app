<?php

namespace App\Console\Commands;

use App\Services\CompanyProvisioner;
use Illuminate\Console\Command;

class CreateCompany extends Command
{
    protected $signature = 'company:create
        {id : Short id, e.g. acme}
        {--name= : Display name, defaults to the id}
        {--domain= : Full domain, defaults to {id}.localhost}
        {--email=admin@example.com}
        {--password=ChangeMe123!}';

    protected $description = 'Create a company with its own database, migrations and admin user';

    public function handle(CompanyProvisioner $provisioner): int
    {
        $id     = strtolower((string) $this->argument('id'));
        $name   = $this->option('name') ?: ucfirst($id);
        $domain = $this->option('domain') ?: "$id.localhost";

        try {
            $provisioner->create(
                ['id' => $id, 'domain' => $domain],
                [
                    'name'     => $name . ' Admin',
                    'email'    => $this->option('email'),
                    'password' => $this->option('password'),
                ],
            );
        } catch (\Throwable $e) {
            $this->error('Could not create the company: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Company '$id' ready at http://$domain:8000");
        return self::SUCCESS;
    }
}