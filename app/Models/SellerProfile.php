<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SellerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'store_name',
        'store_slug',
        'store_logo',
        'store_banner',
        'description',
        'address',
        'phone',
        'website',
        'facebook',
        'instagram',
        'twitter',
        'youtube',
        'status',
        'commission_rate',
        'warehouse_address',
        'pickup_address',
        'return_address',
        'working_hours',
        'vacation_mode',
        'verification_status',
        'store_policies',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate'    => 'decimal:2',
            'vacation_mode'      => 'boolean',
            'working_hours'      => 'array',
            'store_policies'     => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SellerProfile $profile) {
            if (empty($profile->store_slug)) {
                $profile->store_slug = Str::slug($profile->store_name);
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        return $this->store_logo ? asset('storage/' . $this->store_logo) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->store_banner ? asset('storage/' . $this->store_banner) : null;
    }
}
