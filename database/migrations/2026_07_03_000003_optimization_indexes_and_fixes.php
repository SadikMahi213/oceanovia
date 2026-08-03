<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Coupon date indexes
        Schema::table('coupons', function (Blueprint $table) {
            $table->index('starts_at');
            $table->index('expires_at');
        });

        // Product filter indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_digital');
            $table->index('scheduled_at');
        });

        // Inventory log created_at index
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        // Tax rate active index
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->index('is_active');
        });

        // KYC verifications verified_by index
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->index('verified_by');
        });

        // Change users.phone to 30 chars for international numbers
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
        });

        // Change user_notifications.data to json type
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->json('data')->nullable()->change();
        });

        // Fix inventory_logs.reference_id to unsignedBigInteger
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('reference_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['starts_at']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_digital']);
            $table->dropIndex(['scheduled_at']);
        });

        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropIndex(['verified_by']);
        });
    }
};
