<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ActivityLog extends Model
{
    use CentralConnection;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public static function record(string $action, ?string $tenantId = null, array $details = []): void
    {
        $admin = Auth::guard('super')->user();

        static::create([
            'super_admin_id' => $admin?->id,
            'super_admin_name' => $admin?->name ?? 'system',
            'action' => $action,
            'tenant_id' => $tenantId,
            'details' => $details ?: null,
            'ip' => request()->ip(),
        ]);
    }
}