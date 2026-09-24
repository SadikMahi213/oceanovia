<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->decimal('wholesale_price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->string('sku', 100);
            $table->string('barcode', 100)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->string('material', 255)->nullable();
            $table->json('colors')->nullable();
            $table->json('sizes')->nullable();
            $table->json('tags')->nullable();
            $table->json('images')->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'sku']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};