<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'seller_id',
        'supplier_id',
        'supplier_product_id',
        'unit_cost',
        'product_name',
        'sku',
        'quantity',
        'held_quantity',
        'stock_held_at',
        'stock_confirmed_at',
        'stock_declined_at',
        'unit_price',
        'subtotal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'held_quantity'     => 'integer',
            'stock_held_at'     => 'datetime',
            'stock_confirmed_at'=> 'datetime',
            'stock_declined_at' => 'datetime',
            'unit_price'        => 'decimal:2',
            'unit_cost'         => 'decimal:2',
            'subtotal'          => 'decimal:2',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
    }

    public function codCollection(): HasOne
    {
        return $this->hasOne(CodCollection::class);
    }

    /**
     * Margin (retail - commission - sourcing cost) for sourced items.
     */
    public function getNetForSellerAttribute(): float
    {
        return round((float) $this->subtotal - (float) ($this->unit_cost ?? 0) * $this->quantity, 2);
    }

    // ─── Stock hold ─────────────────────────────────────────────────────────

    public const STATUS_AWAITING_STOCK = 'awaiting_stock';

    /**
     * Whether this item is waiting for the seller/supplier to confirm that the
     * shortfall can be sourced.
     */
    public function isStockHeld(): bool
    {
        return $this->status === self::STATUS_AWAITING_STOCK;
    }

    /**
     * The quantity that was actually reserved and is billable. The requested
     * quantity minus the shortfall that is held.
     */
    public function confirmedQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->held_quantity);
    }
}
