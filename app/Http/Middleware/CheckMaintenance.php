<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Support\CompanySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never block provider callbacks.
        if ($request->is('payhero/callback*')) {
            return $next($request);
        }

        $platformOn = PlatformSetting::get('maintenance_mode', '0') === '1';
        $companyOn  = CompanySettings::get('maintenance_mode', '0') === '1';

        if (! $platformOn && ! $companyOn) {
            return $next($request);
        }

        $allowed = array_filter(array_map('trim', explode(',', (string) PlatformSetting::get('maintenance_ips', ''))));
        if (in_array($request->ip(), $allowed, true)) {
            return $next($request);
        }

        $message = $companyOn
            ? CompanySettings::get('maintenance_message')
            : PlatformSetting::get('maintenance_message');

        return response()->view('maintenance', [
            'message' => $message ?: 'We are performing maintenance. Please check back shortly.',
            'name'    => CompanySettings::get('business_name') ?: PlatformSetting::get('platform_name', 'POS'),
            'contact' => PlatformSetting::get('support_email'),
        ], 503)->header('Retry-After', '900');
    }
}