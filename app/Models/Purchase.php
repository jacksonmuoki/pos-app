<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['reference', 'supplier_id', 'user_id', 'purchase_date', 'total', 'notes'];

    protected $casts = ['purchase_date' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}