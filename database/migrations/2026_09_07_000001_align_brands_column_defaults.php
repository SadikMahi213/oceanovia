<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align the production brands table with the declared schema in
     * 2026_06_27_000006_create_brands_table (sort_order default 0,
     * is_active default true). Production drifted: both columns are
     * NOT NULL without defaults, so any omitted-column insert fails.
     * Raw ALTER is used because doctrine/dbal is not installed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('brands') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `brands` MODIFY `sort_order` INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `brands` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        if (! Schema::hasTable('brands') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `brands` MODIFY `sort_order` INT NOT NULL');
        DB::statement('ALTER TABLE `brands` MODIFY `is_active` TINYINT(1) NOT NULL');
    }
};
