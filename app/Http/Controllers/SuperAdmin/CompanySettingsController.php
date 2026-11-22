<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Support\CompanySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(Tenant $company): View
    {
        [$values, $hasSecret] = $company->run(fn () => CompanySettings::load());

        return view('super.company-settings', [
            'company'   => $company,
            'values'    => $values,
            'hasSecret' => $hasSecret,
        ]);
    }

    public function update(Request $request, Tenant $company): RedirectResponse
    {
        $data = $request->validate(CompanySettings::rules() + [
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        $changed = $company->run(function () use ($request, $data) {
            $changed = CompanySettings::save($request, $data);

            $maintenance = $request->boolean('maintenance_mode') ? '1' : '0';
            if (\App\Support\CompanySettings::get('maintenance_mode', '0') !== $maintenance) {
                $changed[] = 'maintenance_mode';
            }
            CompanySettings::put('maintenance_mode', $maintenance);
            CompanySettings::put('maintenance_message', $data['maintenance_message'] ?? null);

            return $changed;
        });

        // Field names only, never values.
        ActivityLog::record('company.settings_updated', $company->id, 'Company settings changed', [
            'fields_changed' => $changed,
        ]);

        return back()->with('status', "Settings saved for {$company->id}.");
    }
}