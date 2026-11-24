<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\CompanySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        [$values, $hasSecret] = CompanySettings::load();

        return view('settings.edit', [
            'values'      => $values,
            'hasSecret'   => $hasSecret,
            'maintenance' => ($values['maintenance_mode'] ?? '0') === '1',
            'mpesaReady'  => CompanySettings::payhero() !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(CompanySettings::rules());

        // M-Pesa cannot be switched on without complete credentials.
        if ($request->boolean('mpesa_enabled')) {
            $complete = filled($data['payhero_username'] ?? null)
                && filled($data['payhero_channel_id'] ?? null)
                && (filled($data['payhero_password'] ?? null) || CompanySettings::get('payhero_password'));

            if (! $complete) {
                return back()
                    ->withErrors(['mpesa_enabled' => 'Enter the PayHero username, password and channel ID before enabling M-Pesa.'])
                    ->withInput($request->except('payhero_password', 'sms_api_key'));
            }
        }

        CompanySettings::save($request, $data);

        return redirect()->route('settings.edit')->with('status', 'Settings saved.');
    }
}