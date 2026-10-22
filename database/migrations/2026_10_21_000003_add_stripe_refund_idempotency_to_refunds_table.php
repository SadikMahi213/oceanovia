<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give a refund the identity of its external operation.
 *
 * A card refund is sent to the payment provider outside the database
 * transaction, so the database has to remember which refund was already sent
 * and under which key:
 *
 *  - stripe_idempotency_key is the key the provider call carries. It is
 *    derived once and then kept, so a repeated attempt asks the provider for
 *    the same refund instead of creating a second one. The unique index makes
 *    sure two refunds can never claim the same key.
 *  - stripe_refund_id is the refund the provider created. It is the proof
 *    that the money moved, and the unique index makes sure two refunds can
 *    never point at the same provider refund.
 *  - stripe_refunded_at is when the provider confirmed it.
 *
 * All three columns are nullable, so the existing refunds keep working and
 * the unique indexes are not violated by the rows that were refunded before
 * this migration (a unique index ignores the rows whose value is NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->string('stripe_idempotency_key', 191)->nullable()->unique();
            $table->string('stripe_refund_id', 191)->nullable()->unique();
            $table->timestamp('stripe_refunded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropUnique(['stripe_idempotency_key']);
            $table->dropUnique(['stripe_refund_id']);
            $table->dropColumn(['stripe_idempotency_key', 'stripe_refund_id', 'stripe_refunded_at']);
        });
    }
};
