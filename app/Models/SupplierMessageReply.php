<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_message_id',
        'user_id',
        'message',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupplierMessage::class, 'supplier_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
