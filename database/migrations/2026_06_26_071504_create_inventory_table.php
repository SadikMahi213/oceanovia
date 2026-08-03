<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('stock_quantity')->default(0);
            $table->integer('stock_alert_threshold')->default(5);
            $table->string('warehouse_location')->nullable();
            $table->timestamps();

            $table->index('stock_quantity');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
