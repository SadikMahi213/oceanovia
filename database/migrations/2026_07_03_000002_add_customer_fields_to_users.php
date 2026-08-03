<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('avatar');
            $table->date('date_of_birth')->nullable()->after('cover_image');
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->string('country', 100)->nullable()->after('gender');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('state');
            $table->json('notification_preferences')->nullable()->after('postal_code');
            $table->timestamp('last_login_at')->nullable()->after('notification_preferences');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image', 'date_of_birth', 'gender', 'country',
                'city', 'state', 'postal_code', 'notification_preferences',
                'last_login_at', 'last_login_ip',
            ]);
        });
    }
};
