<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_order_id',
        'order_item_id',
        'product_id',
        'supplier_product_id',
        'product_name',
        'sku',
        'quantity',
        'unit_cost',
        'subtotal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_cost'  => 'decimal:2',
            'subtotal'   => 'decimal:2',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function procurementOrder(): BelongsTo
    {
        return $this->belongsTo(ProcurementOrder::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'          => 'Pending',
            'processing'       => 'Processing',
            'packed'           => 'Packed',
            'ready_for_pickup' => 'Ready for Pickup',
            'shipped'          => 'Shipped',
            'delivered'        => 'Delivered',
            'cancelled'        => 'Cancelled',
            'returned'         => 'Returned',
            default            => ucfirst($this->status),
        };
    }
}