<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ) {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You must be logged in.');
        }

        if (! $user->isActive()) {
            abort(403, 'Your account is inactive.');
        }

        // Company owners have full company access.
        if ($user->isCompanyOwner()) {
            return $next($request);
        }

        // Normalize role names to avoid case mismatches.
        $allowedRoles = array_map('strtolower', $roles);

        $legacyRole = strtolower((string) $user->role);

        $assignedRoles = $user->getRoleNames()
            ->map(fn ($role) => strtolower($role))
            ->all();

        if (
            in_array($legacyRole, $allowedRoles, true)
            || ! empty(array_intersect($assignedRoles, $allowedRoles))
        ) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
