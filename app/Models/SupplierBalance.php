<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'balance',
        'pending_balance',
        'total_earned',
        'total_withdrawn',
        'platform_fees',
    ];

    protected function casts(): array
    {
        return [
            'balance'         => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_earned'    => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
            'platform_fees'   => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
}
