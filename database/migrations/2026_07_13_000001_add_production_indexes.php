<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders: customer order history
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'orders_user_status_created_idx');
            $table->index(['status', 'payment_status'], 'orders_status_payment_idx');
        });

        // Order items: seller/supplier views
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['seller_id', 'order_id'], 'order_items_seller_order_idx');
            $table->index(['supplier_id', 'order_id'], 'order_items_supplier_order_idx');
        });

        // Products: seller product list, category browsing
        Schema::table('products', function (Blueprint $table) {
            $table->index(['seller_id', 'status', 'created_at'], 'products_seller_status_created_idx');
            $table->index(['category_id', 'status', 'price'], 'products_category_status_price_idx');
        });

        // Reviews: product page aggregation
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'is_approved', 'rating'], 'reviews_product_approved_rating_idx');
        });

        // Seller payouts: history
        Schema::table('seller_payouts', function (Blueprint $table) {
            $table->index(['seller_id', 'status', 'created_at'], 'seller_payouts_status_created_idx');
        });

        // Commissions: finance
        Schema::table('commissions', function (Blueprint $table) {
            $table->index(['seller_id', 'status', 'created_at'], 'commissions_seller_status_created_idx');
        });

        // Carts: guest session lookup
        Schema::table('carts', function (Blueprint $table) {
            $table->index(['session_id', 'user_id'], 'carts_session_user_idx');
        });

        // Recently viewed: cleanup + history
        Schema::table('recently_viewed', function (Blueprint $table) {
            $table->index(['user_id', 'product_id', 'updated_at'], 'recently_viewed_user_product_idx');
        });

        // Inventory: stock alerts
        Schema::table('inventory', function (Blueprint $table) {
            $table->index(['stock_quantity', 'stock_alert_threshold'], 'inventory_stock_alert_idx');
        });

        // Notifications: user inbox
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_inbox_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_status_created_idx');
            $table->dropIndex('orders_status_payment_idx');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_seller_order_idx');
            $table->dropIndex('order_items_supplier_order_idx');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_seller_status_created_idx');
            $table->dropIndex('products_category_status_price_idx');
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_product_approved_rating_idx');
        });
        Schema::table('seller_payouts', function (Blueprint $table) {
            $table->dropIndex('seller_payouts_status_created_idx');
        });
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropIndex('commissions_seller_status_created_idx');
        });
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_session_user_idx');
        });
        Schema::table('recently_viewed', function (Blueprint $table) {
            $table->dropIndex('recently_viewed_user_product_idx');
        });
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex('inventory_stock_alert_idx');
        });
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_inbox_idx');
        });
    }
};
