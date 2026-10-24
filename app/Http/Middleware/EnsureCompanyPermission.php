<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You must be logged in.');
        }

        if (! $user->isActive()) {
            abort(403, 'Your account is inactive.');
        }

        // The Company Owner has full company-level access.
        if ($user->isCompanyOwner()) {
            return $next($request);
        }

        if (! $user->hasPermissionTo($permission, 'web')) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
