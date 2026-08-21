<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            // Drop the global unique constraint that blocks reuse after soft delete.
            // MySQL does not support partial unique indexes (WHERE deleted_at IS NULL),
            // so we rely on application validation (Rule::unique()->whereNull('deleted_at'))
            // which is the standard Laravel pattern for soft-deletes.
            // Keep a plain index for lookup performance.
            $table->dropUnique(['state_code']);
            $table->index('state_code');
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropIndex(['state_code']);

            // Re-create unique constraint. Will fail if duplicate active codes exist,
            // which is expected — duplicates should be resolved before rolling back.
            $table->unique('state_code');
        });
    }
};
