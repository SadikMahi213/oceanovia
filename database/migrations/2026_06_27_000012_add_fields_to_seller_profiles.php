<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->text('warehouse_address')->nullable()->after('address');
            $table->text('pickup_address')->nullable()->after('warehouse_address');
            $table->text('return_address')->nullable()->after('pickup_address');
            $table->json('working_hours')->nullable()->after('return_address');
            $table->boolean('vacation_mode')->default(false)->after('working_hours');
            $table->string('verification_status', 30)->default('unverified')->after('vacation_mode');
            // unverified, pending, verified
            $table->json('store_policies')->nullable()->after('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'warehouse_address',
                'pickup_address',
                'return_address',
                'working_hours',
                'vacation_mode',
                'verification_status',
                'store_policies',
            ]);
        });
    }
};
