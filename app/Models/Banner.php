<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'link',
        'image',
        'mobile_image',
        'btn_text',
        'text_color',
        'bg_color',
        'sort_order',
        'section',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'     => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->mobile_image ? asset('storage/' . $this->mobile_image) : null;
    }
}
