<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'quarterly_price',
        'annual_price',
        'currency',
        'max_users',
        'max_branches',
        'max_products',
        'storage_limit_mb',
        'trial_days',
        'is_active',
        'is_popular',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'quarterly_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'max_users' => 'integer',
            'max_branches' => 'integer',
            'max_products' => 'integer',
            'storage_limit_mb' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * Get all features belonging to this plan.
     */
    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get enabled features only.
     */
    public function enabledFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class)
            ->where('is_enabled', true);
    }

    /**
     * Get all subscriptions using this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the plan price for a billing cycle.
     */
    public function getPriceForCycle(string $cycle): float
    {
        return match ($cycle) {
            'monthly' => (float) $this->monthly_price,
            'quarterly' => (float) $this->quarterly_price,
            'annual' => (float) $this->annual_price,
            default => throw new \InvalidArgumentException(
                'Invalid billing cycle.'
            ),
        };
    }

    /**
     * Check whether a feature is enabled.
     */
    public function hasFeature(string $slug): bool
    {
        return $this->enabledFeatures()
            ->where('slug', $slug)
            ->exists();
    }
}
