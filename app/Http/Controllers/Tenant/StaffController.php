<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $selectedRole = trim((string) $request->query('role', ''));
        $status = trim((string) $request->query('status', ''));

        $staffQuery = User::query()
            ->with('roles')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $staffQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($selectedRole !== '') {
            $staffQuery->where(function ($query) use ($selectedRole) {
                $query->whereHas('roles', function ($roleQuery) use ($selectedRole) {
                    $roleQuery->where('name', $selectedRole)
                        ->where('guard_name', 'web');
                })->orWhere('role', $selectedRole);
            });
        }

        if ($status === 'active') {
            $staffQuery->where('active', true);
        } elseif ($status === 'inactive') {
            $staffQuery->where('active', false);
        }

        $staff = $staffQuery->paginate(15)->withQueryString();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.staff.index', [
            'user' => $request->user(),
            'staff' => $staff,
            'roles' => $roles,
            'search' => $search,
            'selectedRole' => $selectedRole,
            'status' => $status,
            'totalStaff' => User::count(),
            'activeStaff' => User::where('active', true)->count(),
            'inactiveStaff' => User::where('active', false)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor && $actor->isActive(), 403);

        $allowedRoleNames = $this->allowedRolesFor($actor)
            ->pluck('name')
            ->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role' => [
                'required',
                'string',
                Rule::in($allowedRoleNames),
            ],
            'pin' => [
                'required',
                'string',
                'regex:/^[0-9]{4,6}$/',
                'confirmed',
            ],
        ], [
            'pin.regex' => 'The PIN must contain 4 to 6 digits.',
            'pin.confirmed' => 'The PIN confirmation does not match.',
        ]);

        foreach (User::query()->get(['id', 'password']) as $existingUser) {
            if (Hash::check($validated['pin'], $existingUser->password)) {
                throw ValidationException::withMessages([
                    'pin' => 'This PIN is already assigned to another account. Choose a different PIN.',
                ]);
            }
        }

        DB::transaction(function () use ($validated) {
            $selectedRole = Role::findByName($validated['role'], 'web');

            $employee = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => $validated['pin'],
                'role' => $this->legacyRole($selectedRole->name),
                'active' => true,
            ]);

            $employee->assignRole($selectedRole);
        });

        return redirect()
            ->route('staff.index')
            ->with('success', 'Employee account created successfully.');
    }

    public function edit(Request $request, User $employee): View
    {
        $actor = $request->user();

        abort_unless($actor && $actor->isActive(), 403);

        $this->authorizeStaffTarget($actor, $employee);

        $employee->load('roles');

        return view('tenant.staff.edit', [
            'employee' => $employee,
            'roles' => $this->allowedRolesFor($actor)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor && $actor->isActive(), 403);

        $this->authorizeStaffTarget($actor, $employee);

        $allowedRoleNames = $this->allowedRolesFor($actor)
            ->pluck('name')
            ->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee->id),
            ],
            'role' => [
                'required',
                'string',
                Rule::in($allowedRoleNames),
            ],
        ]);

        DB::transaction(function () use ($employee, $validated) {
            $selectedRole = Role::findByName($validated['role'], 'web');

            $employee->update([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'role' => $this->legacyRole($selectedRole->name),
            ]);

            $employee->syncRoles([$selectedRole]);
        });

        return redirect()
            ->route('staff.index')
            ->with('success', 'Employee details updated successfully. Their existing PIN was not changed.');
    }

    public function toggleStatus(Request $request, User $employee): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor && $actor->isActive(), 403);

        $this->authorizeStaffTarget($actor, $employee);

        $employee->update([
            'active' => ! $employee->active,
        ]);

        $message = $employee->active
            ? 'Employee account activated successfully.'
            : 'Employee account deactivated successfully.';

        return redirect()
            ->route('staff.index')
            ->with('success', $message);
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor && $actor->isActive(), 403);

        $this->authorizeStaffTarget($actor, $employee);

        // Check whether this employee has historical orders.
        // Do not delete orders or break their employee references.
        $hasOrders = DB::table('orders')
            ->where('user_id', $employee->id)
            ->exists();

        if ($hasOrders) {
            return redirect()
                ->route('staff.index')
                ->with(
                    'error',
                    'This employee cannot be permanently deleted because existing orders are linked to their account. Deactivate the account instead.'
                );
        }

        DB::transaction(function () use ($employee) {
            $employee->syncRoles([]);
            $employee->delete();
        });

        return redirect()
            ->route('staff.index')
            ->with('success', 'Employee account deleted successfully.');
    }

    private function allowedRolesFor(User $actor)
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->where('name', '!=', 'Company Owner');

        if (! $actor->isCompanyOwner()) {
            $query->where('name', '!=', 'Admin');
        }

        return $query->orderBy('name');
    }

    private function authorizeStaffTarget(User $actor, User $employee): void
    {
        abort_if(
            $actor->is($employee),
            403,
            'You cannot manage your own account here.'
        );

        abort_if(
            $employee->isCompanyOwner(),
            403,
            'The Company Owner account cannot be managed from staff management.'
        );

        if (! $actor->isCompanyOwner()) {
            abort_if(
                $employee->hasRole('Admin'),
                403,
                'You are not allowed to manage an Admin account.'
            );
        }
    }

    private function legacyRole(string $role): string
    {
        return match ($role) {
            'Admin' => 'admin',
            'Cashier' => 'cashier',
            default => 'manager',
        };
    }
}
