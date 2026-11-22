<?php // app/Http/Controllers/SuperAdmin/BackupController.php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Tenant;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(Request $request): View
    {
        $query = Backup::query();
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        return view('super.backups', [
            'backups'   => $query->latest('created_at')->paginate(20)->withQueryString(),
            'tenants'   => Tenant::pluck('id'),
            'retention' => config('services.backup.retention_days'),
        ]);
    }

    public function store(Request $request, BackupService $service): RedirectResponse
    {
        $request->validate(['tenant_id' => ['nullable', 'string']]);

        $tenants = $request->filled('tenant_id')
            ? Tenant::whereKey($request->input('tenant_id'))->get()
            : Tenant::all();

        foreach ($tenants as $tenant) {
            $backup = $service->backup($tenant);
            ActivityLog::record('backup.created', $tenant->id, "Backup {$backup->status}", ['size' => $backup->size]);
        }

        return back()->with('status', 'Backup run finished.');
    }

    public function download(Backup $backup): StreamedResponse
    {
        abort_unless($backup->status === 'completed' && $backup->path, 404);
        ActivityLog::record('backup.downloaded', $backup->tenant_id, "Backup #{$backup->id}");

        return Storage::disk('local')->download($backup->path, "{$backup->tenant_id}_{$backup->created_at->format('Ymd_His')}.sql");
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        if ($backup->path) {
            Storage::disk('local')->delete($backup->path);
        }
        ActivityLog::record('backup.deleted', $backup->tenant_id, "Backup #{$backup->id}");
        $backup->delete();

        return back()->with('status', 'Backup deleted.');
    }
}