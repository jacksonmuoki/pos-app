<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Throwable;

class ActivityLog extends Model
{
    use CentralConnection;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    /**
     * Supported call styles:
     *   ActivityLog::record('company.suspended', 'acme', ['reason' => 'unpaid']);
     *   ActivityLog::record('backup.created', 'acme', 'Backup completed', ['size' => 1024]);
     *   ActivityLog::record('settings.updated', null, 'Settings changed');
     */
    public static function record(
        string $action,
        ?string $tenantId = null,
        string|array|null $description = null,
        array $properties = [],
    ): void {
        // Old style: third argument is the details array.
        if (is_array($description)) {
            $properties  = $description;
            $description = null;
        }

        $details = $properties;
        if ($description !== null && $description !== '') {
            $details = ['description' => $description] + $details;
        }

        try {
            $admin = Auth::guard('super')->user();

            static::create([
                'super_admin_id'   => $admin?->id,
                'super_admin_name' => $admin?->name ?? 'system',
                'action'           => $action,
                'tenant_id'        => $tenantId,
                'details'          => $details ?: null,
                'ip'               => request()->ip(),
                'created_at'       => now(),
            ]);
        } catch (Throwable $e) {
            // Logging must never break the action being logged.
            report($e);
        }
    }

    /** Lets views use $row->description. */
    public function getDescriptionAttribute(): ?string
    {
        return $this->details['description'] ?? null;
    }
}