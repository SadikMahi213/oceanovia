<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'compare_price',
        'cost_per_item',
        'sku',
        'barcode',
        'weight',
        'height',
        'width',
        'length',
        'material',
        'colors',
        'sizes',
        'tags',
        'images',
        'video_url',
        'is_digital',
        'downloadable_file',
        'scheduled_at',
        'unit',
        'status',
        'is_featured',
        'total_views',
        'total_sold',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'compare_price'   => 'decimal:2',
            'cost_per_item'  => 'decimal:2',
            'weight'         => 'decimal:2',
            'height'         => 'decimal:2',
            'width'          => 'decimal:2',
            'length'         => 'decimal:2',
            'is_featured'    => 'boolean',
            'is_digital'     => 'boolean',
            'scheduled_at'   => 'datetime',
            'total_views'    => 'integer',
            'total_sold'     => 'integer',
            'colors'         => 'array',
            'sizes'          => 'array',
            'tags'           => 'array',
            'images'         => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(6);
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('inventory', fn($q) => $q->where('stock_quantity', '>', 0))
              ->orWhereHas('variants', fn($q) => $q->where('stock_quantity', '>', 0));
        });
    }

    public function scopeHasActiveVariants($query)
    {
        return $query->whereHas('variants', fn($q) => $q->active());
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeMaxPrice($query, float $price)
    {
        return $query->where('price', '<=', $price);
    }

    public function scopeMinPrice($query, float $price)
    {
        return $query->where('price', '>=', $price);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('status', 'draft');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('short_description', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhereJsonContains('tags', $term);
        });
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getThumbnailAttribute(): ?string
    {
        $images = $this->images;
        if (!empty($images) && isset($images[0])) {
            return asset('storage/' . $images[0]);
        }
        return null;
    }

    public function getImageUrlsAttribute(): array
    {
        $images = $this->images ?? [];
        return array_map(fn ($img) => asset('storage/' . $img), $images);
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return (int) round((1 - $this->price / $this->compare_price) * 100);
        }
        return null;
    }

    public function getRatingAverageAttribute(): float
    {
        return round($this->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->where('is_approved', true)->count();
    }

    public function getInStockAttribute(): bool
    {
        return $this->inventory()->where('stock_quantity', '>', 0)->exists();
    }

    public function getStockQuantityAttribute(): int
    {
        return $this->inventory ? $this->inventory->stock_quantity : 0;
    }

    public function getMinVariantPriceAttribute(): ?string
    {
        $min = $this->variants()->active()->min('price');
        return $min ?? $this->price;
    }

    public function getMaxVariantPriceAttribute(): ?string
    {
        $max = $this->variants()->active()->max('price');
        return $max ?? $this->price;
    }
}
