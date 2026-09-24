<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'order_id',
        'supplier_id',
        'seller_id',
        'status',
        'subtotal',
        'total_quantity',
        'tracking_number',
        'carrier',
        'tracking_url',
        'accepted_at',
        'packed_at',
        'ready_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'settled_at',
        'cancellation_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'      => 'decimal:2',
            'total_quantity' => 'integer',
            'accepted_at'   => 'datetime',
            'packed_at'     => 'datetime',
            'ready_at'      => 'datetime',
            'shipped_at'    => 'datetime',
            'delivered_at'  => 'datetime',
            'cancelled_at'  => 'datetime',
            'settled_at'    => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProcurementOrder $procurementOrder) {
            if (empty($procurementOrder->po_number)) {
                $procurementOrder->po_number = 'PO-' . strtoupper(uniqid());
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementOrderItem::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeBySeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSettled($query)
    {
        return $query->whereNotNull('settled_at');
    }

    public function scopeUnsettled($query)
    {
        return $query->whereNull('settled_at');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getIsSettledAttribute(): bool
    {
        return $this->settled_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'              => 'Pending',
            'processing'           => 'Processing',
            'packed'               => 'Packed',
            'ready_for_pickup'     => 'Ready for Pickup',
            'shipped'              => 'Shipped',
            'delivered'            => 'Delivered',
            'cancelled'            => 'Cancelled',
            'returned'             => 'Returned',
            default                => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'          => 'yellow',
            'processing'       => 'indigo',
            'packed'           => 'cyan',
            'ready_for_pickup' => 'blue',
            'shipped'          => 'purple',
            'delivered'        => 'green',
            'cancelled'        => 'red',
            'returned'         => 'gray',
            default            => 'gray',
        };
    }
}