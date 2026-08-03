<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_price',
        'stock_quantity',
        'weight',
        'color',
        'size',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'compare_price'  => 'decimal:2',
            'stock_quantity' => 'integer',
            'weight'         => 'decimal:2',
            'is_active'      => 'boolean',
            'sort_order'     => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }
}
