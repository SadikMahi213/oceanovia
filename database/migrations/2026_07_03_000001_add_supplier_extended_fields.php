<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('company_name');
            $table->string('company_banner')->nullable()->after('company_logo');
            $table->string('trade_license')->nullable()->after('website');
            $table->string('vat_number')->nullable()->after('trade_license');
            $table->text('warehouse_address')->nullable()->after('address');
            $table->text('pickup_address')->nullable()->after('warehouse_address');
            $table->text('return_address')->nullable()->after('pickup_address');
            $table->string('contact_email')->nullable()->after('phone');
            $table->string('contact_person')->nullable()->after('contact_email');
            $table->json('bank_account')->nullable()->after('contact_person');
            $table->json('wallet_settings')->nullable()->after('bank_account');
            $table->json('payment_settings')->nullable()->after('wallet_settings');
            $table->json('shipping_preferences')->nullable()->after('payment_settings');
            $table->json('working_hours')->nullable()->after('shipping_preferences');
            $table->json('holiday_calendar')->nullable()->after('working_hours');
            // KYC fields
            $table->string('national_id')->nullable()->after('holiday_calendar');
            $table->string('passport')->nullable()->after('national_id');
            $table->string('business_license_file')->nullable()->after('passport');
            $table->string('tax_certificate')->nullable()->after('business_license_file');
            $table->string('company_registration_doc')->nullable()->after('tax_certificate');
            $table->string('bank_verification_doc')->nullable()->after('company_registration_doc');
            $table->string('address_verification_doc')->nullable()->after('bank_verification_doc');
            $table->string('kyc_status', 20)->default('pending')->after('address_verification_doc');
            $table->text('kyc_rejection_reason')->nullable()->after('kyc_status');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_rejection_reason');
            $table->foreignId('kyc_verified_by')->nullable()->constrained('users')->nullOnDelete()->after('kyc_verified_at');
        });

        Schema::create('supplier_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('pending_balance', 12, 2)->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_withdrawn', 12, 2)->default(0);
            $table->decimal('platform_fees', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('supplier_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->string('payment_method', 50);
            $table->json('account_details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('supplier_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('sender');
            $table->string('subject');
            $table->text('message');
            $table->string('type', 30)->default('admin'); // admin, customer
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'is_read']);
        });

        Schema::create('supplier_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->json('countries')->nullable();
            $table->json('states')->nullable();
            $table->json('cities')->nullable();
            $table->json('zip_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('supplier_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->constrained('supplier_shipping_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('carrier', 50)->nullable();
            $table->enum('type', ['flat', 'weight_based', 'order_total_based', 'free'])->default('flat');
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('min_weight', 10, 2)->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->decimal('min_order_total', 10, 2)->nullable();
            $table->decimal('max_order_total', 10, 2)->nullable();
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_shipping_rates');
        Schema::dropIfExists('supplier_shipping_zones');
        Schema::dropIfExists('supplier_message_replies');
        Schema::dropIfExists('supplier_messages');
        Schema::dropIfExists('supplier_payouts');
        Schema::dropIfExists('supplier_balances');

        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'brand_name', 'company_banner', 'trade_license', 'vat_number',
                'warehouse_address', 'pickup_address', 'return_address',
                'contact_email', 'contact_person', 'bank_account',
                'wallet_settings', 'payment_settings', 'shipping_preferences',
                'working_hours', 'holiday_calendar',
                'national_id', 'passport', 'business_license_file',
                'tax_certificate', 'company_registration_doc',
                'bank_verification_doc', 'address_verification_doc',
                'kyc_status', 'kyc_rejection_reason', 'kyc_verified_at',
                'kyc_verified_by',
            ]);
        });
    }
};
