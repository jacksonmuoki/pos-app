<?php

namespace App\Console\Commands;

use App\Models\SuperAdmin;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    protected $signature = 'super:create {email} {--name=Super Admin} {--password=}';

    protected $description = 'Create a central super admin';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (SuperAdmin::where('email', $email)->exists()) {
            $this->error("A super admin with $email already exists.");
            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        SuperAdmin::create([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
        ]);

        $this->info("Super admin $email created. Sign in at /super/login on the central domain.");
        return self::SUCCESS;
    }
}