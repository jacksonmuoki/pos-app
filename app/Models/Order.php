<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'receipt_no', 'user_id', 'customer_id', 'subtotal', 'discount', 'tax',
        'total', 'status', 'table_no', 'change_due', 'paid_at',
    ];

    protected $casts = [
        'paid_at'    => 'datetime',
        'subtotal'   => 'decimal:2',
        'discount'   => 'decimal:2',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
        'change_due' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['user_id'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->when($f['method'] ?? null, fn ($q, $m) => $q->whereHas('payments', fn ($p) => $p->where('method', $m)))
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) =>
                $q->where('receipt_no', 'like', "%$s%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))));
    }
}