<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = SuperAdmin::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $admins = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('super.admins.index', [
            'admins' => $admins,
            'search' => $request->input('search', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:super_admins,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $admin = SuperAdmin::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::record('super_admin.created', null, [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_email' => $admin->email,
        ]);

        return redirect()
            ->route('super.admins.index')
            ->with('success', 'Super Admin account created successfully.');
    }

    public function destroy(SuperAdmin $admin): RedirectResponse
    {
        $currentAdmin = auth('super')->user();

        abort_unless($currentAdmin, 403);

        if ((int) $currentAdmin->id === (int) $admin->id) {
            return redirect()
                ->route('super.admins.index')
                ->with('error', 'You cannot delete your own Super Admin account.');
        }

        $deletedAdmin = [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_email' => $admin->email,
        ];

        $admin->delete();

        ActivityLog::record('super_admin.deleted', null, $deletedAdmin);

        return redirect()
            ->route('super.admins.index')
            ->with('success', 'Super Admin account deleted successfully.');
    }
}
