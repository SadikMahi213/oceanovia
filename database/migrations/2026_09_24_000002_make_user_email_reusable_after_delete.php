<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the global unique email constraint that blocks email reuse
            // after a soft-deleted account. MySQL does not support partial
            // unique indexes (WHERE deleted_at IS NULL), so active-email
            // uniqueness is enforced by application validation
            // (Rule::unique(...)->whereNull('deleted_at')) — the same documented
            // pattern used for tax_rates in fix_tax_rates_soft_delete_unique.
            // Keep a plain index for email lookups.
            $table->dropUnique(['email']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);

            // Re-create the unique email constraint. Will fail if duplicate
            // active emails exist, which is expected — duplicates should be
            // resolved before rolling back.
            $table->unique('email');
        });
    }
};
