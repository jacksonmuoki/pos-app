<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockSuspendedCompany
{
    public function handle(Request $request, Closure $next)
    {
        if (tenant('suspended')) {
            abort(403, 'This company account is suspended. Please contact support.');
        }

        return $next($request);
    }
}