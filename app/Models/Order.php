<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'shipping_cost',
        'tax',
        'discount',
        'total',
        'status',
        'payment_method',
        'payment_status',
        'stripe_payment_intent_id',
        'stripe_session_id',
        'shipping_address_id',
        'billing_address_id',
        'notes',
        'admin_notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'       => 'decimal:2',
            'shipping_cost'  => 'decimal:2',
            'tax'            => 'decimal:2',
            'discount'       => 'decimal:2',
            'total'          => 'decimal:2',
            'confirmed_at'   => 'datetime',
            'shipped_at'     => 'datetime',
            'delivered_at'   => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * The number a new order carries.
     *
     * The number has to be unique across every process that can place an
     * order, so it is built from a ULID: 48 bits of millisecond timestamp and
     * 80 bits of randomness, rendered as 26 characters. Inside one process the
     * generator is monotonic, so two orders created in the same millisecond
     * never share a number, and the random part differs between processes, so
     * two requests that create an order at the same moment do not collide
     * either. The unique index on the column stays the last line of defence.
     *
     * uniqid() was not safe here: it is derived from the clock alone, so two
     * processes that created an order in the same microsecond produced the
     * same number and the second insert failed with a duplicate key error
     * (MySQL 1062) instead of an order.
     *
     * The optional timestamp is what makes that guarantee testable: two calls
     * for the same instant still return different numbers.
     */
    public static function generateOrderNumber(?\DateTimeInterface $time = null): string
    {
        return 'ORD-'.(string) Str::ulid($time);
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'Pending',
            'confirmed'  => 'Confirmed',
            'processing' => 'Processing',
            'shipped'    => 'Shipped',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
            'refunded'   => 'Refunded',
            default      => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'yellow',
            'confirmed'  => 'blue',
            'processing' => 'indigo',
            'shipped'    => 'purple',
            'delivered'  => 'green',
            'cancelled'  => 'red',
            'refunded'   => 'gray',
            default      => 'gray',
        };
    }
}
