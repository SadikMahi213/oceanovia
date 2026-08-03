<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable(); // original/msrp price
            $table->decimal('cost_per_item', 12, 2)->nullable();
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->string('material')->nullable();
            $table->json('colors')->nullable();
            $table->json('sizes')->nullable();
            $table->json('tags')->nullable();
            $table->json('images')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published, archived
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_sold')->default(0);
            $table->string('meta_title', 500)->nullable();
            $table->string('meta_description', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('status');
            $table->index('is_featured');
            $table->index('seller_id');
            $table->index('category_id');
            $table->index('price');
            // $table->fullText(['name', 'description', 'short_description']); // Requires MySQL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
