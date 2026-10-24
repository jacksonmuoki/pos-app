<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query();

        // Search by administrator, action, company or IP address.
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($builder) use ($search) {
                $builder->where('super_admin_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('tenant_id', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%");
            });
        }

        // Filter by activity type.
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter by company.
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        $activities = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $companies = ActivityLog::query()
            ->whereNotNull('tenant_id')
            ->select('tenant_id')
            ->distinct()
            ->orderBy('tenant_id')
            ->pluck('tenant_id');

        return view('super.activity.index', compact(
            'activities',
            'actions',
            'companies'
        ));
    }
}
