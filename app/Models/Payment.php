<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'method', 'amount', 'phone', 'status',
        'external_reference', 'provider_reference', 'callback_payload', 'paid_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'callback_payload' => 'array',
        'paid_at'          => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['method'] ?? null, fn ($q, $m) => $q->where('method', $m))
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) =>
                $q->where('phone', 'like', "%$s%")
                  ->orWhere('external_reference', 'like', "%$s%")
                  ->orWhere('provider_reference', 'like', "%$s%")));
    }
}