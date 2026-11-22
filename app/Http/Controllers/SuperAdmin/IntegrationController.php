<?php // app/Http/Controllers/SuperAdmin/IntegrationController.php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(): View
    {
        $saved = Integration::all()->keyBy('provider');
        return view('super.integrations', [
            'providers' => Integration::PROVIDERS,
            'saved'     => $saved,
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        abort_unless(isset(Integration::PROVIDERS[$provider]), 404);

        $fields = Integration::PROVIDERS[$provider]['fields'];
        $request->validate(collect($fields)->mapWithKeys(
            fn ($f, $name) => ["config.$name" => ['nullable', 'string', 'max:255']]
        )->all());

        $integration = Integration::firstOrNew(['provider' => $provider]);
        $config = $integration->config ?? [];

        foreach ($fields as $name => $def) {
            $value = $request->input("config.$name");
            // Blank secret = keep the existing one.
            if ($def['secret'] && ($value === null || $value === '')) {
                continue;
            }
            $config[$name] = $value;
        }

        $integration->config  = $config;
        $integration->enabled = $request->boolean('enabled');
        $integration->save();

        ActivityLog::record('integration.updated', null, "Integration {$provider} updated", [
            'provider' => $provider, 'enabled' => $integration->enabled,
        ]);

        return back()->with('status', ucfirst($provider) . ' integration saved.');
    }
}