<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Support\CompanySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    private const DEFAULTS = [
        'platform_name'       => 'POS Platform',
        'support_email'       => '',
        'support_phone'       => '',
        'default_timezone'    => 'Africa/Nairobi',
        'default_currency'    => 'KES',
        'date_format'         => 'd M Y',
        'trial_days'          => '14',
        'signup_enabled'      => '1',
        'maintenance_mode'    => '0',
        'maintenance_message' => 'We are performing scheduled maintenance. Please check back shortly.',
        'maintenance_ips'     => '',
    ];

    public function edit(): View
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = PlatformSetting::get($key, $default);
        }

        $companies = [];
        foreach (Tenant::all() as $tenant) {
            $row = ['id' => $tenant->id, 'currency' => '-', 'name' => '-', 'maintenance' => false, 'sms' => false, 'payhero' => false, 'error' => false];
            try {
                $tenant->run(function () use (&$row) {
                    $row['currency']    = CompanySettings::currency();
                    $row['name']        = CompanySettings::get('business_name', '-');
                    $row['maintenance'] = CompanySettings::get('maintenance_mode') === '1';
                    $row['sms']         = CompanySettings::get('sms_enabled') === '1';
                    $row['payhero']     = CompanySettings::payhero() !== null;
                });
            } catch (Throwable) {
                $row['error'] = true;
            }
            $companies[] = $row;
        }

        return view('super.settings', compact('settings', 'companies'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name'       => ['required', 'string', 'max:100'],
            'support_email'       => ['required', 'email', 'max:255'],
            'support_phone'       => ['nullable', 'string', 'max:30'],
            'default_timezone'    => ['required', 'timezone'],
            'default_currency'    => ['required', 'string', 'size:3', 'alpha'],
            'date_format'         => ['required', 'string', 'max:20'],
            'trial_days'          => ['required', 'integer', 'min:0', 'max:365'],
            'maintenance_message' => ['required', 'string', 'max:500'],
            'maintenance_ips'     => ['nullable', 'string', 'max:500'],
        ]);

        // Allowed IPs: comma separated, each must be a valid IP.
        $ips = collect(explode(',', (string) ($data['maintenance_ips'] ?? '')))
            ->map(fn ($ip) => trim($ip))->filter()->values();

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                return back()->withErrors(['maintenance_ips' => "Invalid IP address: {$ip}"])->withInput();
            }
        }

        $data['maintenance_ips']  = $ips->implode(',');
        $data['default_currency'] = strtoupper($data['default_currency']);
        $data['signup_enabled']   = $request->boolean('signup_enabled') ? '1' : '0';
        $data['maintenance_mode'] = $request->boolean('maintenance_mode') ? '1' : '0';

        $old = [];
        foreach ($data as $key => $value) {
            $old[$key] = PlatformSetting::get($key);
            PlatformSetting::put($key, $value);
        }

        ActivityLog::record('settings.updated', null, 'Platform settings changed', ['old' => $old, 'new' => $data]);

        return back()->with('status', 'Settings saved.');
    }
}