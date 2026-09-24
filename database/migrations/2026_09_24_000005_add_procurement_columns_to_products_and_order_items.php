<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_product_id')->nullable()->after('seller_id')
                ->constrained('supplier_products')->nullOnDelete();
            $table->decimal('sourcing_price', 10, 2)->nullable()->after('supplier_product_id');

            $table->index('supplier_product_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('supplier_product_id')->nullable()->after('supplier_id')
                ->constrained('supplier_products')->nullOnDelete();
            $table->decimal('unit_cost', 10, 2)->nullable()->after('supplier_product_id');

            $table->index('supplier_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['supplier_product_id']);
            $table->dropColumn(['supplier_product_id', 'unit_cost']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['supplier_product_id']);
            $table->dropColumn(['supplier_product_id', 'sourcing_price']);
        });
    }
};