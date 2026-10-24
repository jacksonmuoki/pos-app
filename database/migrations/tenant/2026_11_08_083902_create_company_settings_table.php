<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section', 60);
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Create the permissions used by the company workspace.
        $permissions = [
            'view sales',
            'create sales',
            'edit sales',
            'void sales',
            'apply discounts',
            'view products',
            'manage products',
            'view customers',
            'manage customers',
            'manage inventory',
            'adjust stock',
            'view reports',
            'export reports',
            'manage expenses',
            'manage staff',
            'manage settings',
            'manage payments',
            'manage branches',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Give the existing Admin role access to settings initially.
        // The Company Owner always has full access through the application.
        $admin = Role::where('name', 'Admin')
            ->where('guard_name', 'web')
            ->first();

        if ($admin) {
            $admin->givePermissionTo('manage settings');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');

        // Deliberately preserve permissions and roles because they may
        // already be used by staff accounts and other application modules.
    }
};
