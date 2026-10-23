<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock-hold columns for order items.
 *
 * An item whose full requested quantity was not available at checkout stays on
 * the order in the "awaiting_stock" status. held_quantity holds the shortfall
 * (requested minus reserved), so a partial fill is representable: 5 requested
 * with 3 reserved means held_quantity = 2. The timestamps record when the hold
 * was opened and how it was resolved.
 *
 * The columns are additive, defaulted and nullable, so existing rows are
 * unaffected and no backfill is needed. The order_items.status column is a
 * plain varchar with no check constraint, so the new "awaiting_stock" value
 * needs no schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('held_quantity')->default(0)->after('quantity');
            $table->timestamp('stock_held_at')->nullable()->after('held_quantity');
            $table->timestamp('stock_confirmed_at')->nullable()->after('stock_held_at');
            $table->timestamp('stock_declined_at')->nullable()->after('stock_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'held_quantity',
                'stock_held_at',
                'stock_confirmed_at',
                'stock_declined_at',
            ]);
        });
    }
};
