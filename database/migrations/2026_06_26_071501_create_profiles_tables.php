<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('store_name');
            $table->string('store_slug')->unique();
            $table->string('store_logo')->nullable();
            $table->string('store_banner')->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('twitter')->nullable();
            $table->string('youtube')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, suspended
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('store_slug');
        });

        Schema::create('supplier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_slug')->unique();
            $table->string('company_logo')->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, suspended
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('company_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_profiles');
        Schema::dropIfExists('seller_profiles');
    }
};
