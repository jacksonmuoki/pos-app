<?php // app/Models/Backup.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Backup extends Model
{
    use CentralConnection;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'path', 'size', 'status', 'error', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}