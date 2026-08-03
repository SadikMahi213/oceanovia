<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'amount',
        'platform_fee',
        'tax',
        'net_amount',
        'payment_method',
        'account_details',
        'status',
        'admin_notes',
        'rejection_reason',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'platform_fee'   => 'decimal:2',
            'tax'            => 'decimal:2',
            'net_amount'     => 'decimal:2',
            'account_details' => 'array',
            'processed_at'   => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'rejected'  => 'Rejected',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'yellow',
            'processing' => 'indigo',
            'completed' => 'green',
            'rejected'  => 'red',
            default     => 'gray',
        };
    }
}
