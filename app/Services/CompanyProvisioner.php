<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CompanyProvisioner
{
    /**
     * @param array{id: string, domain: string} $tenant
     * @param array{name: string, email: string, password: string} $admin
     */
    public function create(array $tenant, array $admin): Tenant
    {
        $id = $tenant['id'];
        $domain = $tenant['domain'];
        $dbName = 'tenant' . $id;

        if (! preg_match('/^[a-z][a-z0-9-]{2,39}$/', $id)) {
            throw new \InvalidArgumentException(
                'Company id must be 3 to 40 characters: lowercase letters, numbers or hyphens, starting with a letter.'
            );
        }

        $tenantModel = new Tenant();

        if ($tenantModel->getKeyType() !== 'string' || $tenantModel->getIncrementing()) {
            throw new \RuntimeException(
                'The Tenant model must use a string key with incrementing set to false.'
            );
        }

        if (Tenant::query()->whereKey($id)->exists()) {
            throw new \RuntimeException("Company '$id' already exists.");
        }

        if ($this->databaseExists($dbName)) {
            throw new \RuntimeException(
                "Database '$dbName' is left over from an earlier attempt. Drop it or choose another id."
            );
        }

        $created = null;

        try {
            // Create tenant.
            $created = Tenant::forceCreate(['id' => $id]);

            // Register tenant domain.
            $created->domains()->create([
                'domain' => $domain,
            ]);

            // Initialize tenant database.
            tenancy()->initialize($created);

            // Clear permission cache before seeding.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Create all tenant roles and permissions.
            $this->seedRoles();

            // Ensure Admin role exists.
            $adminRole = Role::firstOrCreate([
                'name' => 'Admin',
                'guard_name' => 'web',
            ]);

            // Give Admin every existing web permission.
            $permissions = Permission::where('guard_name', 'web')->get();

            $adminRole->syncPermissions($permissions);

            // Create first tenant administrator.
            $owner = User::create([
                'name' => $admin['name'],
                'email' => strtolower($admin['email']),
                'password' => Hash::make($admin['password']),
                'role' => 'admin',
                'active' => true,
            ]);

            // Assign Admin role automatically.
            $owner->assignRole($adminRole);

            // Clear permission cache after assignment.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // End tenant context.
            tenancy()->end();

            return $created;

        } catch (\Throwable $e) {
            tenancy()->end();

            if ($created) {
                $created->delete();
            }

            $this->dropDatabaseIfExists($dbName);

            throw $e;
        }
    }

    /**
     * Initialize tenant roles and permissions.
     */
    private function seedRoles(): void
    {
        app(\Database\Seeders\Tenant\RolesAndPermissionsSeeder::class)->run();
    }

    private function databaseExists(string $name): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$name]
        );
    }

    private function dropDatabaseIfExists(string $name): void
    {
        if ($this->databaseExists($name)) {
            DB::statement("DROP DATABASE `{$name}`");
        }
    }
}
