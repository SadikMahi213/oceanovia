<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('brands')->nullOnDelete();
            $table->string('unit', 50)->nullable()->after('barcode');
            // piece, kg, liter, etc.
            $table->string('video_url', 500)->nullable()->after('images');
            $table->boolean('is_digital')->default(false)->after('video_url');
            $table->string('downloadable_file', 255)->nullable()->after('is_digital');
            $table->timestamp('scheduled_at')->nullable()->after('downloadable_file');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn([
                'brand_id',
                'unit',
                'video_url',
                'is_digital',
                'downloadable_file',
                'scheduled_at',
            ]);
        });
    }
};
