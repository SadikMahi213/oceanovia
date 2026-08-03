<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'username',
        'email',
        'phone',
        'password',
        'avatar',
        'cover_image',
        'role_type',
        'status',
        'date_of_birth',
        'gender',
        'country',
        'city',
        'state',
        'postal_code',
        'notification_preferences',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    // ─── Marketplace Relationships ──────────────────────────────────────────

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function supplierProfile(): HasOne
    {
        return $this->hasOne(SupplierProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function recentlyViewed(): HasMany
    {
        return $this->hasMany(RecentlyViewed::class);
    }

    // ─── Role Helpers ───────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role_type === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->role_type === 'seller';
    }

    public function isSupplier(): bool
    {
        return $this->role_type === 'supplier';
    }

    public function isCustomer(): bool
    {
        return $this->role_type === 'customer';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->lastname
            ? trim($this->name . ' ' . $this->lastname)
            : $this->name;
    }

    /**
     * Get the redirect route based on user role.
     */
    public function getDashboardRoute(): string
    {
        return match ($this->role_type) {
            'admin'    => '/admin/dashboard',
            'seller'   => '/seller/dashboard',
            'supplier' => '/supplier/dashboard',
            default    => '/dashboard',
        };
    }

    /**
     * Get the avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : asset('images/no_avatar.png');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include users of a given role type.
     */
    public function scopeOfType($query, string $roleType)
    {
        return $query->where('role_type', $roleType);
    }
}
