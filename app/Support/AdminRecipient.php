<?php

namespace App\Support;

use App\Models\User;

class AdminRecipient
{
    /**
     * Resolve the single inbox that receives admin alerts.
     *
     * Prefers mail.admin_address; otherwise falls back to the oldest admin
     * account so alerts never fan out to every admin.
     *
     * @return array{user_id: ?int, email: ?string, name: string}|null
     */
    public static function resolve(): ?array
    {
        $configured = config('mail.admin_address');

        if (is_string($configured) && $configured !== '') {
            $user = User::query()
                ->where('role_type', 'admin')
                ->where('email', $configured)
                ->first();

            return [
                'user_id' => $user?->id,
                'email'   => $configured,
                'name'    => $user?->name ?? 'Admin',
            ];
        }

        $user = User::query()
            ->where('role_type', 'admin')
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();

        if ($user === null) {
            return null;
        }

        return [
            'user_id' => $user->id,
            'email'   => $user->email,
            'name'    => $user->name,
        ];
    }
}
