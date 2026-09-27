<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'title',
        'icon',
        'link',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'id'      => 'string',
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    // ─── Accessors ─────────────────────────────────────────────────────────

    /**
     * Notification views render $notification->message; the message is stored
     * inside the JSON data payload so it can stay schema-stable.
     */
    public function getMessageAttribute(): ?string
    {
        return $this->data['message'] ?? null;
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }
}
