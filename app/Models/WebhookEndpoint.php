<?php // app/Models/WebhookEndpoint.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class WebhookEndpoint extends Model
{
    use CentralConnection;

    protected $fillable = ['url', 'secret', 'events', 'active'];

    protected $casts = [
        'secret' => 'encrypted',
        'events' => 'array',
        'active' => 'boolean',
    ];

    public const EVENTS = [
        'company.created', 'company.suspended', 'company.activated',
        'company.deleted', 'backup.completed', 'backup.failed',
        'payment.succeeded',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}