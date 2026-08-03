<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 50);
            // passport, drivers_license, national_id, business_license
            $table->string('document_number', 100)->nullable();
            $table->string('document_front', 255);
            $table->string('document_back', 255)->nullable();
            $table->string('selfie', 255)->nullable();
            $table->string('status', 30)->default('pending');
            // pending, approved, rejected
            $table->text('admin_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
