<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CompanyRoleSeeder
{
    public function seed(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',

            'manage company',
            'manage staff',
            'manage roles',
            'manage permissions',
            'view activity logs',

            'view sales',
            'create sales',
            'edit sales',
            'cancel sales',

            'view products',
            'create products',
            'edit products',
            'delete products',

            'view categories',
            'manage categories',

            'view inventory',
            'manage inventory',

            'view customers',
            'manage customers',

            'view payments',
            'manage payments',

            'view reports',
            'export reports',

            'manage settings',
            'manage shifts',
            'view receipts',
            'manage receipts',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'Company Owner' => $permissions,

            'Admin' => [
                'view dashboard',
                'manage staff',
                'manage roles',
                'manage permissions',
                'view activity logs',
                'view sales',
                'create sales',
                'edit sales',
                'cancel sales',
                'view products',
                'create products',
                'edit products',
                'delete products',
                'view categories',
                'manage categories',
                'view inventory',
                'manage inventory',
                'view customers',
                'manage customers',
                'view payments',
                'manage payments',
                'view reports',
                'export reports',
                'manage settings',
                'manage shifts',
                'view receipts',
                'manage receipts',
            ],

            'Manager' => [
                'view dashboard',
                'view sales',
                'create sales',
                'edit sales',
                'view products',
                'create products',
                'edit products',
                'view categories',
                'manage categories',
                'view inventory',
                'manage inventory',
                'view customers',
                'manage customers',
                'view payments',
                'view reports',
                'manage shifts',
                'view receipts',
            ],

            'Accountant' => [
                'view dashboard',
                'view sales',
                'view payments',
                'manage payments',
                'view reports',
                'export reports',
                'view receipts',
            ],

            'Supervisor' => [
                'view dashboard',
                'view sales',
                'create sales',
                'edit sales',
                'view products',
                'view inventory',
                'view customers',
                'view reports',
                'manage shifts',
                'view receipts',
            ],

            'Cashier' => [
                'view dashboard',
                'view sales',
                'create sales',
                'view customers',
                'view receipts',
            ],

            'Storekeeper' => [
                'view dashboard',
                'view products',
                'create products',
                'edit products',
                'view categories',
                'view inventory',
                'manage inventory',
            ],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $rolePermissionNames)
                    ->get()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
