<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $admin = auth('super')->user();

        abort_unless($admin, 403);

        return view('super.profile.edit', [
            'admin' => $admin,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = auth('super')->user();

        abort_unless($admin, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique($admin->getTable(), 'email')->ignore($admin->getKey()),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password:super'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $admin->name = $validated['name'];
        $admin->email = $validated['email'];

        if (!empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        }

        $admin->save();

        return redirect()
            ->route('super.profile.edit')
            ->with('success', 'Your profile has been updated.');
    }
}
