<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_rate',
        'rate_per_kg',
        'free_shipping_threshold',
        'estimated_days_min',
        'estimated_days_max',
        'zones',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_rate'               => 'decimal:2',
            'rate_per_kg'             => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'zones'                   => 'array',
            'is_active'               => 'boolean',
            'sort_order'              => 'integer',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getEstimatedDaysAttribute(): ?string
    {
        if ($this->estimated_days_min && $this->estimated_days_max) {
            return "{$this->estimated_days_min}-{$this->estimated_days_max} days";
        }

        if ($this->estimated_days_min) {
            return "{$this->estimated_days_min}+ days";
        }

        if ($this->estimated_days_max) {
            return "Up to {$this->estimated_days_max} days";
        }

        return null;
    }
}
