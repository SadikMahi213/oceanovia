<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name',
        'countries',
        'states',
        'cities',
        'zip_codes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'states'    => 'array',
            'cities'    => 'array',
            'zip_codes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(SupplierShippingRate::class, 'shipping_zone_id');
    }
}
