<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_read_by_seller')->default(false);
            $table->boolean('is_read_by_customer')->default(false);
            $table->timestamps();

            $table->index('order_id');
            $table->index('seller_id');
            $table->index('user_id');
            $table->index('is_read_by_seller');
            $table->index('is_read_by_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_messages');
    }
};
