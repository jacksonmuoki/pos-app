<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Backup;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class BackupService
{
    public function backup(Tenant $tenant): Backup
    {
        $backup = Backup::create([
            'tenant_id'  => $tenant->id,
            'status'     => 'running',
            'created_at' => now(),
        ]);

        $disk     = Storage::disk('local');
        $relative = "backups/{$tenant->id}/" . now()->format('Ymd_His') . '.sql';
        $disk->makeDirectory("backups/{$tenant->id}");
        $absolute = str_replace('/', DIRECTORY_SEPARATOR, $disk->path($relative));

        $optionsFile = null;

        try {
            $cfg = config('database.connections.mysql');
            $db  = $tenant->database()->getName();

            // Credentials in a temp file: keeps the password out of the command line.
            $optionsFile = tempnam(sys_get_temp_dir(), 'mydump');
            file_put_contents($optionsFile, "[client]\nuser=\"{$cfg['username']}\"\npassword=\"" . addcslashes((string) ($cfg['password'] ?? ''), '"\\') . "\"\nhost=\"{$cfg['host']}\"\nport={$cfg['port']}\n");

            $process = new Process([
                config('services.backup.mysqldump'),
                '--defaults-extra-file=' . $optionsFile,   // must be the first option
                '--single-transaction',
                '--routines',
                '--result-file=' . $absolute,
                $db,
            ]);

            // Windows needs SystemRoot (and PATH) or sockets cannot be created.
            $env = array_merge($_SERVER, $_ENV, getenv() ?: []);
            $env = array_filter($env, fn ($v) => is_string($v));
            $env['SystemRoot'] = $env['SystemRoot'] ?? getenv('SystemRoot') ?: 'C:\\Windows';
            $env['PATH']       = $env['PATH'] ?? getenv('PATH') ?: 'C:\\Windows\\System32';
            $process->setEnv($env);

            $process->setTimeout(600)->mustRun();

            if (! is_file($absolute) || filesize($absolute) === 0) {
                throw new \RuntimeException('Dump file is empty.');
            }

            $backup->update([
                'path'   => $relative,
                'size'   => filesize($absolute),
                'status' => 'completed',
            ]);
        } catch (Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'error'  => substr($e->getMessage(), 0, 1500),
            ]);
            $disk->delete($relative);
        } finally {
            if ($optionsFile && is_file($optionsFile)) {
                @unlink($optionsFile);
            }
        }

        return $backup->fresh();
    }

    public function prune(): int
    {
        $cutoff = now()->subDays(config('services.backup.retention_days'));
        $old    = Backup::where('created_at', '<', $cutoff)->get();

        foreach ($old as $b) {
            if ($b->path) {
                Storage::disk('local')->delete($b->path);
            }
            $b->delete();
        }

        return $old->count();
    }
}