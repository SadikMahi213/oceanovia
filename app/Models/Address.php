<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'address_type',
        'first_name',
        'last_name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
        'country',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeBilling($query)
    {
        return $query->where('address_type', 'billing');
    }

    public function scopeShipping($query)
    {
        return $query->where('address_type', 'shipping');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->zip,
        ]);

        return implode(', ', $parts);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
