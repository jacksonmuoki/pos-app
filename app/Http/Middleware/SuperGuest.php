<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperGuest
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('super')->check()) {
            return redirect()->route('super.dashboard');
        }

        return $next($request);
    }
}