<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'seller_id',
        'body',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
