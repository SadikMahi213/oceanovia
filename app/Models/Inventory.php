<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'stock_quantity',
        'stock_alert_threshold',
        'warehouse_location',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity'        => 'integer',
            'stock_alert_threshold' => 'integer',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'stock_alert_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->stock_alert_threshold;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_quantity <= 0;
    }
}
