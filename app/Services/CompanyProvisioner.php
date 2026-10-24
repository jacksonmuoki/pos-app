<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CompanyProvisioner
{
    /**
     * @param array{id: string, domain: string} $tenant
     * @param array{name: string, email: string, password: string} $admin
     */
    public function create(array $tenant, array $admin): Tenant
    {
        $id     = $tenant['id'];
        $domain = $tenant['domain'];
        $dbName = 'tenant' . $id;

        if (! preg_match('/^[a-z][a-z0-9-]{2,39}$/', $id)) {
            throw new \InvalidArgumentException(
                'Company id must be 3 to 40 characters: lowercase letters, numbers or hyphens, starting with a letter.'
            );
        }

        if (! (new Tenant)->getKeyType() === 'string' || (new Tenant)->getIncrementing()) {
            throw new \RuntimeException('The Tenant model must use a string key with incrementing set to false.');
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
            // forceCreate sets the id directly, so mass assignment cannot drop it.
            $created = Tenant::forceCreate(['id' => $id]);

            if ($created->getKey() !== $id) {
                throw new \RuntimeException("Tenant id was saved as '{$created->getKey()}' instead of '$id'.");
            }

            $created->domains()->create(['domain' => $domain]);

            tenancy()->initialize($created);

            $this->seedRoles();

            $owner = User::create([
                'name'     => $admin['name'],
                'email'    => $admin['email'],
                'password' => Hash::make($admin['password']),
                'role'     => 'admin',
                'active'   => true,
            ]);

            if (Role::where('name', 'admin')->exists()) {
                $owner->assignRole('admin');
            }

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
     * Call your existing role and permission seeder here, otherwise the new
     * company's staff will get 403 errors on permission-protected pages.
     */
    private function seedRoles(): void
    {
        // app(\Database\Seeders\Tenant\RolesAndPermissionsSeeder::class)->run();
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