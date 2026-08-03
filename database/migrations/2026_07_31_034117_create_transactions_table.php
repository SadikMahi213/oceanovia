<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('accountable'); // seller/supplier balance owner
            $table->nullableMorphs('reference'); // order, payout, refund
            $table->string('type'); // credit, debit, payout, refund
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('status')->default('completed'); // pending, completed, failed
            $table->string('method')->nullable(); // stripe, cod, paypal, bank...
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
