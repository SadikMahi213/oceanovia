<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'amount',
        'fee',
        'net_amount',
        'status',
        'payment_method',
        'account_details',
        'notes',
        'approved_by',
        'approved_at',
        'completed_at',
        'paid_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'account_details' => 'array',
            'approved_at'     => 'datetime',
            'completed_at'    => 'datetime',
            'rejected_at'     => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'payout_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeBySeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}
