<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SettingsController extends Controller
{
    private function permissionGroups(): array
    {
        return [
            'Sales and POS' => [
                'view sales' => 'View sales',
                'create sales' => 'Create sales',
                'edit sales' => 'Edit sales',
                'void sales' => 'Cancel or void sales',
                'apply discounts' => 'Apply discounts',
            ],
            'Products and customers' => [
                'view products' => 'View products',
                'manage products' => 'Add and edit products',
                'view customers' => 'View customers',
                'manage customers' => 'Manage customers',
            ],
            'Inventory' => [
                'manage inventory' => 'Manage inventory',
                'adjust stock' => 'Adjust stock quantities',
            ],
            'Finance and reports' => [
                'view reports' => 'View reports',
                'export reports' => 'Export reports',
                'manage expenses' => 'Manage expenses',
                'manage payments' => 'Manage payment settings',
            ],
            'Company administration' => [
                'manage staff' => 'Manage staff accounts',
                'manage settings' => 'Manage company settings',
                'manage branches' => 'Manage branches',
            ],
        ];
    }

    private function authorizeSettings(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && $user->isActive(), 403);

        if ($user->isCompanyOwner()) {
            return;
        }

        abort_unless(
            $user->hasPermissionTo('manage settings', 'web'),
            403,
            'You do not have permission to manage company settings.'
        );
    }

    private function authorizeRoleManagement(Request $request): void
    {
        abort_unless(
            $request->user()?->isCompanyOwner(),
            403,
            'Only the Company Owner can manage company roles and permissions.'
        );
    }

    public function index(Request $request)
    {
        $this->authorizeSettings($request);

        $settings = DB::table('company_settings')
            ->get()
            ->mapWithKeys(function ($setting) {
                return [
                    $setting->key => json_decode($setting->value, true)
                        ?? $setting->value,
                ];
            })
            ->all();

        $defaults = [
            'company_name' => tenant('id') ?? 'My Company',
            'company_email' => '',
            'company_phone' => '',
            'company_address' => '',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
            'date_format' => 'd/m/Y',

            'receipt_size' => '80mm',
            'receipt_footer' => 'Thank you for shopping with us.',
            'receipt_auto_print' => true,

            'allow_cash' => true,
            'allow_mpesa' => true,
            'allow_card' => false,

            'tax_enabled' => false,
            'tax_name' => 'VAT',
            'tax_rate' => 16,

            'allow_negative_stock' => false,
            'low_stock_threshold' => 5,

            'allow_discounts' => true,
            'require_customer' => false,
            'require_manager_void' => true,

            'password_min_length' => 10,
            'session_timeout' => 60,
        ];

        $settings = array_merge($defaults, $settings);

        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $roles = Role::where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $staff = User::with('roles')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $person) => !$person->isCompanyOwner())
            ->values();

        return view('tenant.settings.index', [
            'settings' => $settings,
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionGroups' => $this->permissionGroups(),
            'staff' => $staff,
            'isOwner' => $request->user()->isCompanyOwner(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeSettings($request);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_address' => ['nullable', 'string', 'max:500'],

            'currency' => ['required', Rule::in(['KES', 'USD', 'UGX', 'TZS'])],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', Rule::in(['d/m/Y', 'm/d/Y', 'Y-m-d'])],

            'receipt_size' => ['required', Rule::in(['58mm', '80mm'])],
            'receipt_footer' => ['nullable', 'string', 'max:500'],

            'allow_cash' => ['required', 'boolean'],
            'allow_mpesa' => ['required', 'boolean'],
            'allow_card' => ['required', 'boolean'],
            'receipt_auto_print' => ['required', 'boolean'],

            'tax_enabled' => ['required', 'boolean'],
            'tax_name' => ['required', 'string', 'max:40'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            'allow_negative_stock' => ['required', 'boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],

            'allow_discounts' => ['required', 'boolean'],
            'require_customer' => ['required', 'boolean'],
            'require_manager_void' => ['required', 'boolean'],

            'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
            'session_timeout' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);

        $groups = [
            'company_name' => 'company',
            'company_email' => 'company',
            'company_phone' => 'company',
            'company_address' => 'company',

            'currency' => 'general',
            'timezone' => 'general',
            'date_format' => 'general',

            'receipt_size' => 'pos',
            'receipt_footer' => 'pos',
            'receipt_auto_print' => 'pos',
            'allow_discounts' => 'pos',
            'require_customer' => 'pos',
            'require_manager_void' => 'pos',

            'allow_cash' => 'payments',
            'allow_mpesa' => 'payments',
            'allow_card' => 'payments',

            'tax_enabled' => 'tax',
            'tax_name' => 'tax',
            'tax_rate' => 'tax',

            'allow_negative_stock' => 'inventory',
            'low_stock_threshold' => 'inventory',

            'password_min_length' => 'security',
            'session_timeout' => 'security',
        ];

        DB::transaction(function () use ($validated, $groups) {
            foreach ($validated as $key => $value) {
                DB::table('company_settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'section' => $groups[$key],
                        'value' => json_encode($value),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return back()->with('success', 'Company settings saved successfully.');
    }

    public function storeRole(Request $request)
    {
        $this->authorizeRoleManagement($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $role = Role::create([
            'name' => trim($validated['name']),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'New staff role created successfully.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $this->authorizeRoleManagement($request);

        abort_unless($role->guard_name === 'web', 404);

        // The Company Owner role is protected from all changes.
        abort_if($role->name === 'Company Owner', 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $role->update(['name' => trim($validated['name'])]);
        $role->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role permissions updated successfully.');
    }

    public function destroyRole(Request $request, Role $role)
    {
        $this->authorizeRoleManagement($request);

        abort_unless($role->guard_name === 'web', 404);

        if (in_array($role->name, ['Company Owner', 'Admin'], true)) {
            return back()->with('error', 'This built-in role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with(
                'error',
                'This role is assigned to staff. Reassign those staff members before deleting it.'
            );
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role deleted successfully.');
    }

    public function assignStaffRole(Request $request, User $user)
    {
        $this->authorizeRoleManagement($request);

        abort_if($user->isCompanyOwner(), 403, 'The Company Owner role cannot be changed.');

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
                Rule::notIn(['Company Owner']),
            ],
        ]);

        $role = Role::where('name', $validated['role'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        $user->syncRoles([$role]);

        return back()->with('success', 'Staff role updated successfully.');
    }
}
