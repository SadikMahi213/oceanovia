<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix commissions: add missing payout_id column
        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('payout_id')->nullable()->after('paid_at')->constrained('seller_payouts')->nullOnDelete();
        });

        // Fix seller_payouts: add missing columns needed by model
        Schema::table('seller_payouts', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('completed_at')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('completed_at');
            $table->string('rejection_reason')->nullable()->after('rejected_at');
        });

        // Fix refunds: align schema with model
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('refund_method');
            $table->text('approved_notes')->nullable()->after('approved_by');
            $table->string('rejection_reason')->nullable()->after('approved_notes');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['approved_notes', 'rejection_reason', 'rejected_at']);
            $table->string('refund_method', 50)->nullable();
        });

        Schema::table('seller_payouts', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'completed_at', 'rejected_at', 'rejection_reason']);
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['payout_id']);
            $table->dropColumn('payout_id');
        });
    }
};
