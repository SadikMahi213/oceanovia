<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundReversal extends Model
{
    protected $fillable = [
        'refund_id',
        'order_item_id',
        'seller_id',
        'refund_amount',
        'net_amount',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
