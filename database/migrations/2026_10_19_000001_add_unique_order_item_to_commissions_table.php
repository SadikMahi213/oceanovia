<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('commissions')
            ->select('order_item_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('order_item_id')
            ->groupBy('order_item_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('commissions')
                ->where('order_item_id', $duplicate->order_item_id)
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('commissions', function (Blueprint $table) {
            $table->unique('order_item_id', 'commissions_order_item_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropUnique('commissions_order_item_id_unique');
        });
    }
};
