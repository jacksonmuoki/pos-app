<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $keyType = 'string';

    public function getIncrementing()
    {
        return false;
    }

    public static function getCustomColumns(): array
    {
        return ['id'];
    }

    /**
     * Get all subscriptions belonging to this company.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id', 'id');
    }

    /**
     * Get the latest subscription belonging to this company.
     */
    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class, 'tenant_id', 'id')
            ->latestOfMany();
    }
}
