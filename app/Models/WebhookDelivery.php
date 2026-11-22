<?php // app/Models/WebhookDelivery.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class WebhookDelivery extends Model
{
    use CentralConnection;

    protected $fillable = [
        'webhook_endpoint_id', 'event', 'payload',
        'status_code', 'response', 'success', 'attempts',
    ];

    protected $casts = ['payload' => 'array', 'success' => 'boolean'];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}