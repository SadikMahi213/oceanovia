<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->string('link')->nullable();
            $table->string('image');
            $table->string('mobile_image')->nullable();
            $table->string('btn_text')->nullable();
            $table->string('text_color', 20)->default('#ffffff');
            $table->string('bg_color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('section', 30)->default('hero'); // hero, promo, featured
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('section');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
