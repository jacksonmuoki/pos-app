<?php // app/Models/Integration.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Integration extends Model
{
    use CentralConnection;

    protected $fillable = ['provider', 'config', 'enabled'];

    protected $casts = [
        'config'  => 'encrypted:array',
        'enabled' => 'boolean',
    ];

    /** Field definitions per provider. 'secret' fields are never shown back. */
    public const PROVIDERS = [
        'payhero' => [
            'label' => 'PayHero (platform default)',
            'fields' => [
                'username'   => ['label' => 'API username', 'secret' => false],
                'password'   => ['label' => 'API password', 'secret' => true],
                'channel_id' => ['label' => 'Channel ID', 'secret' => false],
            ],
        ],
        'smtp' => [
            'label' => 'Email (SMTP)',
            'fields' => [
                'host'         => ['label' => 'Host', 'secret' => false],
                'port'         => ['label' => 'Port', 'secret' => false],
                'username'     => ['label' => 'Username', 'secret' => false],
                'password'     => ['label' => 'Password', 'secret' => true],
                'encryption'   => ['label' => 'Encryption (tls/ssl)', 'secret' => false],
                'from_address' => ['label' => 'From address', 'secret' => false],
            ],
        ],
        'sms' => [
            'label' => 'SMS provider',
            'fields' => [
                'provider'  => ['label' => 'Provider name', 'secret' => false],
                'api_key'   => ['label' => 'API key', 'secret' => true],
                'sender_id' => ['label' => 'Sender ID', 'secret' => false],
            ],
        ],
    ];
}