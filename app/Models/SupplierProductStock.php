<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProductStock extends Model
{
    use HasFactory;

    protected $table = 'supplier_product_stock';

    protected $fillable = [
        'supplier_product_id',
        'stock_quantity',
        'reserved_quantity',
        'sold_quantity',
        'stock_alert_threshold',
        'warehouse_location',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity'        => 'integer',
            'reserved_quantity'     => 'integer',
            'sold_quantity'         => 'integer',
            'stock_alert_threshold' => 'integer',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
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

    /**
     * Units available to sell now (unreserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }
}