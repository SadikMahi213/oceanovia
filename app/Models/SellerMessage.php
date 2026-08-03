<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'user_id',
        'subject',
        'message',
        'attachments',
        'is_read_by_seller',
        'is_read_by_customer',
    ];

    protected function casts(): array
    {
        return [
            'attachments'        => 'array',
            'is_read_by_seller'  => 'boolean',
            'is_read_by_customer' => 'boolean',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeUnreadBySeller($query)
    {
        return $query->where('is_read_by_seller', false);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeBySeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}
