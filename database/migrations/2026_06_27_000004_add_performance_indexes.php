<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products: composite index for homepage query
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_featured', 'created_at'], 'idx_products_status_featured_created');
            $table->index(['seller_id', 'status'], 'idx_products_seller_status');
        });

        // Orders: composite for user order history
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
            $table->index(['status', 'created_at'], 'idx_orders_status_created');
        });

        // Order items: composite for seller filter
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['seller_id', 'order_id'], 'idx_order_items_seller_order');
        });

        // Inventory: low stock queries
        Schema::table('inventory', function (Blueprint $table) {
            $table->index(['stock_quantity', 'stock_alert_threshold'], 'idx_inventory_stock_alerts');
        });

        // Cart items: composite for cart loading
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['cart_id', 'product_id'], 'idx_cart_items_cart_product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['idx_products_status_featured_created']);
            $table->dropIndex(['idx_products_seller_status']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['idx_orders_user_created']);
            $table->dropIndex(['idx_orders_status_created']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['idx_order_items_seller_order']);
        });
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex(['idx_inventory_stock_alerts']);
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['idx_cart_items_cart_product']);
        });
    }
};
