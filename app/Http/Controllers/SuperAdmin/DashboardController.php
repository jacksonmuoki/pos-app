<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('domains')
            ->latest()
            ->get();

        return view('super.home', [
            'totalCompanies' => $tenants->count(),
            'activeCompanies' => $tenants->filter(
                fn ($tenant) => !$tenant->suspended
            )->count(),
            'suspendedCompanies' => $tenants->filter(
                fn ($tenant) => (bool) $tenant->suspended
            )->count(),
            'recentCompanies' => $tenants->take(6),
        ]);
    }
}