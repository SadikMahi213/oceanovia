<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_after'  => 'integer',
            'quantity_change' => 'integer',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
