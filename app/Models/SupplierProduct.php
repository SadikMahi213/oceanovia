<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'short_description',
        'wholesale_price',
        'compare_price',
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
        'unit',
        'status',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'wholesale_price' => 'decimal:2',
            'compare_price'   => 'decimal:2',
            'weight'          => 'decimal:2',
            'height'          => 'decimal:2',
            'width'           => 'decimal:2',
            'length'          => 'decimal:2',
            'colors'          => 'array',
            'sizes'           => 'array',
            'tags'            => 'array',
            'images'          => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplierProduct $supplierProduct) {
            if (empty($supplierProduct->slug)) {
                $supplierProduct->slug = Str::slug($supplierProduct->name) . '-' . Str::random(6);
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(SupplierProductStock::class);
    }

    public function listingProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function procurementItems(): HasMany
    {
        return $this->hasMany(ProcurementOrderItem::class);
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

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getThumbnailAttribute(): ?string
    {
        $images = $this->images;
        if (! empty($images) && isset($images[0])) {
            return asset('storage/' . $images[0]);
        }

        return null;
    }

    public function getStockQuantityAttribute(): int
    {
        return $this->stock?->stock_quantity ?? 0;
    }

    public function getReservedQuantityAttribute(): int
    {
        return $this->stock?->reserved_quantity ?? 0;
    }

    public function getAvailableQuantityAttribute(): int
    {
        $stock = $this->stock;
        if (! $stock) {
            return 0;
        }

        return max(0, $stock->stock_quantity - $stock->reserved_quantity);
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Stock currently available (unreserved).
     */
    public function getAvailableStockForSaleAttribute(): int
    {
        return $this->available_quantity;
    }
}