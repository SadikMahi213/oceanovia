<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->string('company_name', 255)->nullable()->after('document_number');
            $table->string('dba_name', 255)->nullable()->after('company_name');
            $table->string('tax_id', 100)->nullable()->after('dba_name');
            $table->string('resale_certificate', 255)->nullable()->after('tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'dba_name', 'tax_id', 'resale_certificate']);
        });
    }
};