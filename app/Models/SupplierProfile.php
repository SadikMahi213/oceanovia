<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupplierProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'brand_name',
        'company_slug',
        'company_logo',
        'company_banner',
        'description',
        'address',
        'warehouse_address',
        'pickup_address',
        'return_address',
        'phone',
        'contact_email',
        'contact_person',
        'website',
        'trade_license',
        'vat_number',
        'bank_account',
        'wallet_settings',
        'payment_settings',
        'shipping_preferences',
        'working_hours',
        'holiday_calendar',
        'status',
        // KYC
        'national_id',
        'passport',
        'business_license_file',
        'tax_certificate',
        'company_registration_doc',
        'bank_verification_doc',
        'address_verification_doc',
        'kyc_status',
        'kyc_rejection_reason',
        'kyc_verified_at',
        'kyc_verified_by',
    ];

    protected function casts(): array
    {
        return [
            'bank_account'         => 'array',
            'wallet_settings'      => 'array',
            'payment_settings'     => 'array',
            'shipping_preferences' => 'array',
            'working_hours'        => 'array',
            'holiday_calendar'     => 'array',
            'kyc_verified_at'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplierProfile $profile) {
            if (empty($profile->company_slug)) {
                $profile->company_slug = Str::slug($profile->company_name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(SupplierBalance::class, 'supplier_id', 'user_id');
    }

    public function balance()
    {
        return $this->hasOne(SupplierBalance::class, 'supplier_id', 'user_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(SupplierPayout::class, 'supplier_id', 'user_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'supplier_id', 'user_id');
    }

    public function shippingZones(): HasMany
    {
        return $this->hasMany(SupplierShippingZone::class, 'supplier_id', 'user_id');
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(SupplierShippingRate::class, 'supplier_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupplierMessage::class, 'supplier_id', 'user_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeKycPending($query)
    {
        return $query->where('kyc_status', 'pending');
    }

    public function scopeKycVerified($query)
    {
        return $query->where('kyc_status', 'verified');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->company_logo ? asset('storage/' . $this->company_logo) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->company_banner ? asset('storage/' . $this->company_banner) : null;
    }

    public function getNationalIdUrlAttribute(): ?string
    {
        return $this->national_id ? asset('storage/' . $this->national_id) : null;
    }

    public function getPassportUrlAttribute(): ?string
    {
        return $this->passport ? asset('storage/' . $this->passport) : null;
    }

    public function getBusinessLicenseUrlAttribute(): ?string
    {
        return $this->business_license_file ? asset('storage/' . $this->business_license_file) : null;
    }

    public function getTaxCertificateUrlAttribute(): ?string
    {
        return $this->tax_certificate ? asset('storage/' . $this->tax_certificate) : null;
    }

    public function getCompanyRegistrationUrlAttribute(): ?string
    {
        return $this->company_registration_doc ? asset('storage/' . $this->company_registration_doc) : null;
    }

    public function getBankVerificationUrlAttribute(): ?string
    {
        return $this->bank_verification_doc ? asset('storage/' . $this->bank_verification_doc) : null;
    }

    public function getAddressVerificationUrlAttribute(): ?string
    {
        return $this->address_verification_doc ? asset('storage/' . $this->address_verification_doc) : null;
    }

    public function getKycStatusLabelAttribute(): string
    {
        return match ($this->kyc_status) {
            'verified'  => 'Verified',
            'rejected'  => 'Rejected',
            'pending'   => 'Pending Review',
            default     => 'Not Submitted',
        };
    }

    public function getKycCompletedAttribute(): bool
    {
        return $this->kyc_status === 'verified';
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) OrderItem::where('supplier_id', $this->user_id)
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('subtotal');
    }

    public function getTotalOrdersAttribute(): int
    {
        return OrderItem::where('supplier_id', $this->user_id)
            ->distinct('order_id')
            ->count('order_id');
    }

    public function getPendingOrdersAttribute(): int
    {
        return OrderItem::where('supplier_id', $this->user_id)
            ->whereIn('status', ['pending', 'processing'])
            ->distinct('order_id')
            ->count('order_id');
    }
}
