<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CompanySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplyCompanyTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tz = CompanySettings::timezone();
            if (in_array($tz, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $tz]);
                date_default_timezone_set($tz);
            }
        } catch (Throwable) {
            // Fall back to the app default if settings are unavailable.
        }

        return $next($request);
    }
}