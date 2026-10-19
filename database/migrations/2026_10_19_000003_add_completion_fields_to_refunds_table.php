<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->string('refund_method', 30)->nullable()->after('status');
            $table->foreignId('completed_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
            $table->text('completion_notes')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['refund_method', 'completed_by', 'completed_at', 'completion_notes']);
        });
    }
};
