<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateCompany extends Command
{
    protected $signature = 'company:create
        {id : Short id, e.g. acme}
        {--domain= : Full domain, defaults to {id}.localhost}
        {--email=admin@example.com}
        {--password=ChangeMe123!}';

    protected $description = 'Create a company with its own database, migrations and admin user';

    public function handle(): int
    {
        $id = $this->argument('id');

        if (Tenant::find($id)) {
            $this->error("Company '$id' already exists.");
            return self::FAILURE;
        }

        $domain = $this->option('domain') ?: "$id.localhost";

        $tenant = Tenant::create(['id' => $id]); // creates the DB and runs tenant migrations
        $tenant->domains()->create(['domain' => $domain]);

        $email = $this->option('email');
        $password = $this->option('password');

        $tenant->run(function () use ($id, $email, $password) {
            User::create([
                'name' => ucfirst($id) . ' Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
            ]);
        });

        $this->info("Company '$id' ready at http://$domain:8000");
        $this->line("Admin: $email (change the password after first login)");

        return self::SUCCESS;
    }
}