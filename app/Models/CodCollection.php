<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'collector_id',
        'collector_role',
        'expected_amount',
        'collected_amount',
        'shortfall_amount',
        'overpaid_amount',
        'status',
        'requires_review',
        'collected_by',
        'collected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount'  => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'shortfall_amount' => 'decimal:2',
            'overpaid_amount'  => 'decimal:2',
            'requires_review'  => 'boolean',
            'collected_at'     => 'datetime',
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

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCollected($query)
    {
        return $query->where('status', 'collected');
    }

    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true);
    }

    public function getIsCollectedAttribute(): bool
    {
        return $this->status === 'collected';
    }
}
