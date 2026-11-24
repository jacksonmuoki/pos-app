<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'tenant_id',
        'subscription_id',
        'issue_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'balance_due',
        'currency',
        'status',
        'notes',
        'metadata',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isOverdue(): bool
    {
        return !in_array($this->status, ['paid', 'cancelled', 'draft'], true)
            && $this->balance_due > 0
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function updatePaymentStatus(): void
    {
        if ($this->status === 'cancelled' || $this->status === 'draft') {
            return;
        }

        $balance = max(
            0,
            (float) $this->total - (float) $this->amount_paid
        );

        $status = match (true) {
            $balance <= 0 => 'paid',
            (float) $this->amount_paid > 0 => 'partial',
            $this->due_date !== null && $this->due_date->isPast() => 'overdue',
            default => 'issued',
        };

        $this->update([
            'balance_due' => $balance,
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($this->paid_at ?? now()) : null,
        ]);
    }
}
