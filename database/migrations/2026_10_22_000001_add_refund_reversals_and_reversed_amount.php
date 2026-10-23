<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->decimal('reversed_amount', 12, 2)->default(0)->after('amount');
        });

        Schema::create('refund_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained('refunds')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('refund_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->timestamps();

            $table->unique(['refund_id', 'order_item_id']);
            $table->index(['order_item_id', 'seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_reversals');

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('reversed_amount');
        });
    }
};
