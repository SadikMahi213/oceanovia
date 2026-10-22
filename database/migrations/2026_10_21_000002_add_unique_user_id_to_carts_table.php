<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce one cart per user.
     *
     * Every cart path assumes a user has at most one cart. A duplicate cannot
     * be repaired automatically - merging two carts would rewrite the
     * quantities the customer sees and would have to break the
     * UNIQUE(cart_id, product_id) constraint on cart_items - so the migration
     * stops and reports instead of touching any data.
     *
     * user_id stays nullable: a NULL user_id is not constrained by a unique
     * index on MySQL, SQLite or PostgreSQL, and the foreign key semantics are
     * unchanged.
     */
    public function up(): void
    {
        $duplicates = DB::table('carts')
            ->select('user_id')
            ->selectRaw('COUNT(*) as cart_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $users = $duplicates->pluck('user_id')->implode(', ');

            throw new RuntimeException(
                "Cannot add the unique index on carts.user_id: {$duplicates->count()} user(s) "
                ."have more than one cart (user_id: {$users}). Resolve the duplicate carts "
                .'manually, then run this migration again.'
            );
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->unique('user_id', 'carts_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // On MySQL/InnoDB the unique index also supports the carts.user_id
            // foreign key, because InnoDB reuses it and drops the index that
            // the constraint had before. The constraint therefore has to be
            // dropped before the index, and restored afterwards with the
            // definition the original schema had. SQLite cannot alter foreign
            // keys and ignores both statements.
            $table->dropForeign(['user_id']);
            $table->dropUnique('carts_user_id_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
