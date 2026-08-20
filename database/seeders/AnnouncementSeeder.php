<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        Announcement::firstOrCreate(
            ['title' => 'Important Message'],
            [
                'content' => "Nazmul Bhai, please knock me on WhatsApp.\nI have somehow lost your phone number from my call log.\n\nWhatsApp: +8801887157047\n\nPlease message me when you see this.",
                'type' => 'warning',
                'is_active' => true,
                'starts_at' => null,
                'expires_at' => null,
            ]
        );

        $this->command->info('Announcement ready: WhatsApp contact message.');
    }
}
