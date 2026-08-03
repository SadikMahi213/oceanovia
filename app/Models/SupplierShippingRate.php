<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'shipping_zone_id',
        'name',
        'carrier',
        'type',
        'rate',
        'min_weight',
        'max_weight',
        'min_order_total',
        'max_order_total',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate'              => 'decimal:2',
            'min_weight'        => 'decimal:2',
            'max_weight'        => 'decimal:2',
            'min_order_total'   => 'decimal:2',
            'max_order_total'   => 'decimal:2',
            'estimated_days_min' => 'integer',
            'estimated_days_max' => 'integer',
            'is_active'         => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(SupplierShippingZone::class, 'shipping_zone_id');
    }
}
