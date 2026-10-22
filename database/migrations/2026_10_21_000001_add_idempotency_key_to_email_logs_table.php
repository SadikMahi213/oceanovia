<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a flow-scoped idempotency key to the email log.
     *
     * The column is nullable on purpose. Only the flows that need exactly
     * once semantics (currently the order placed notification) set it, and a
     * unique index ignores NULL rows on MySQL, SQLite and PostgreSQL alike.
     * The other writers - the admin mailer, the KYC approval mail and the COD
     * collection mails - keep their existing behaviour, and the admin mailer
     * must stay able to send the same subject to the same user twice.
     *
     * The index is a plain unique index over a single nullable column, so it
     * needs no partial index support and no engine specific syntax. On MySQL
     * 8 the column is short enough for the default InnoDB index limit.
     */
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('idempotency_key', 191)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
