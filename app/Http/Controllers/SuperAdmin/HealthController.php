<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class HealthController extends Controller
{
    public function index(): View
    {
        $checks = [];

        $checks[] = $this->check('Central database', function () {
            DB::connection(config('tenancy.database.central_connection', 'mysql'))->getPdo();
            return 'Connected';
        });

        $checks[] = $this->check('Storage writable', function () {
            if (! is_writable(storage_path())) {
                throw new \RuntimeException('storage/ is not writable');
            }
            return 'OK';
        });

        $checks[] = $this->check('Disk free space', function () {
            $free  = disk_free_space(base_path());
            $total = disk_total_space(base_path());
            $pct   = (int) round($free / $total * 100);
            $text  = round($free / 1024 ** 3, 1) . " GB free ({$pct}%)";
            if ($pct < 10) {
                throw new \RuntimeException($text);
            }
            return $text;
        });

        $checks[] = $this->check('mysqldump available', function () {
            $path = config('services.backup.mysqldump');
            if (! is_file($path)) {
                throw new \RuntimeException("Not found: {$path}");
            }
            return 'Found';
        });

        $checks[] = $this->check('Queue / cache / session', function () {
            return 'queue=' . config('queue.default')
                . ', cache=' . config('cache.default')
                . ', session=' . config('session.driver');
        });

        $checks[] = $this->check('Failed jobs', function () {
            if (! Schema::hasTable('failed_jobs')) {
                return 'No failed_jobs table';
            }
            $n = DB::table('failed_jobs')->count();
            if ($n > 0) {
                throw new \RuntimeException("{$n} failed job(s)");
            }
            return 'None';
        });

        $checks[] = $this->check('Scheduler (last backup run)', function () {
            $last = Backup::latest('created_at')->first();
            if (! $last) {
                throw new \RuntimeException('No backups have run yet');
            }
            if ($last->created_at->lt(now()->subDays(2))) {
                throw new \RuntimeException('Last run ' . $last->created_at->diffForHumans());
            }
            return 'Last run ' . $last->created_at->diffForHumans();
        });

        // Per-company checks
        $expected   = count(glob(database_path('migrations/tenant/*.php')) ?: []);
        $tenantRows = [];

        foreach (Tenant::all() as $tenant) {
            $row = [
                'id' => $tenant->id, 'db' => 'OK',
                'migrations' => '-', 'backup' => 'Never', 'ok' => true,
            ];

            try {
                $applied = $tenant->run(fn () => DB::table('migrations')->count());
                $pending = $expected - $applied;
                $row['migrations'] = $pending > 0 ? "{$pending} pending" : 'Up to date';
                $row['ok'] = $pending <= 0;
            } catch (Throwable $e) {
                $row['db'] = 'Unreachable';
                $row['ok'] = false;
            }

            $last = Backup::where('tenant_id', $tenant->id)
                ->where('status', 'completed')
                ->latest('created_at')
                ->first();

            if ($last) {
                $row['backup'] = $last->created_at->diffForHumans();
                if ($last->created_at->lt(now()->subDays(2))) {
                    $row['ok'] = false;
                }
            } else {
                $row['ok'] = false;
            }

            $tenantRows[] = $row;
        }

        return view('super.health', compact('checks', 'tenantRows'));
    }

    private function check(string $name, callable $fn): array
    {
        try {
            return ['name' => $name, 'ok' => true, 'detail' => $fn()];
        } catch (Throwable $e) {
            return ['name' => $name, 'ok' => false, 'detail' => $e->getMessage()];
        }
    }
}