<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'regex:/^[0-9]{4,8}$/'],
        ], [
            'password.required' => 'Please enter your PIN.',
            'password.regex' => 'Your PIN must contain 4 to 8 digits.',
        ]);

        $pin = $validated['password'];

        // Find the active staff account whose password hash
        // matches the submitted PIN.
        $matchedUser = null;

        foreach (User::query()->where('active', true)->get() as $user) {
            if (Hash::check($pin, $user->password)) {
                // Reject ambiguous PINs rather than signing in
                // to an arbitrary account.
                if ($matchedUser !== null) {
                    return back()
                        ->withErrors(['password' => 'This PIN is assigned to more than one account. Contact your administrator.']);
                }

                $matchedUser = $user;
            }
        }

        if (! $matchedUser) {
            return back()
                ->withErrors(['password' => 'Incorrect PIN or inactive account.']);
        }

        Auth::login($matchedUser);

        $request->session()->regenerate();

        $isCashier =
            strtolower((string) $matchedUser->role) === 'cashier'
            || $matchedUser->hasAnyRole(['Cashier', 'cashier'], 'web');

        if ($isCashier) {
            return redirect()->route('cashier.index');
        }

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
