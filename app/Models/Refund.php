<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'amount',
        'reason',
        'status',
        'refund_method',
        'approved_by',
        'approved_notes',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'completed_by',
        'completed_at',
        'completion_notes',
        'stripe_idempotency_key',
        'stripe_refund_id',
        'stripe_refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'approved_at'       => 'datetime',
            'rejected_at'       => 'datetime',
            'completed_at'      => 'datetime',
            'stripe_refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * The refunds the payment provider confirmed but that are not finalized
     * locally yet.
     */
    public function scopeStripeRefunded($query)
    {
        return $query->where('status', 'stripe_refunded');
    }

    /**
     * Whether the payment provider already refunded this refund.
     */
    public function hasProviderRefund(): bool
    {
        return filled($this->stripe_refund_id);
    }
}
