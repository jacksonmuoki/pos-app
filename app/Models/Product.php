<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'description',
        'sku',
        'barcode',
        'price',
        'cost',
        'wholesale_price',
        'stock',
        'reorder_level',
        'unit',
        'product_type',
        'image_path',
        'track_stock',
        'is_taxable',
        'tax_rate',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'stock' => 'integer',
            'reorder_level' => 'integer',
            'track_stock' => 'boolean',
            'is_taxable' => 'boolean',
            'active' => 'boolean',
            'tax_rate' => 'decimal:2',
        ];
    }

    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}