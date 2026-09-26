<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\KycVerification;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminKycApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function createSeller(): User
    {
        return User::factory()->create(['role_type' => 'seller']);
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role_type' => 'admin']);
    }

    private function createPendingSellerProfile(User $seller): SellerProfile
    {
        return SellerProfile::factory()->create([
            'user_id' => $seller->id,
            'status'  => 'pending',
        ]);
    }

    private function approveKyc(User $admin, KycVerification $kyc): void
    {
        $this->actingAs($admin)->post(route('admin.kyc.approve', $kyc))
            ->assertRedirect(route('admin.kyc.index'));
    }

    private function assertCanStoreProduct(User $seller): void
    {
        $category = Category::factory()->create(['status' => true]);

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'name'       => 'Verified Seller Product',
            'price'      => 29.99,
            'status'     => 'published',
            'category_id' => $category->id,
            'description' => 'A product created after admin verification',
        ])->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'name'      => 'Verified Seller Product',
            'seller_id' => $seller->id,
        ]);
    }

    private function assertCannotStoreProduct(User $seller): void
    {
        $category = Category::factory()->create(['status' => true]);

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'name'       => 'Blocked Product',
            'price'      => 29.99,
            'status'     => 'published',
            'category_id' => $category->id,
            'description' => 'A product that must not be created',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('products', [
            'name'      => 'Blocked Product',
            'seller_id' => $seller->id,
        ]);
    }

    public function test_admin_approving_seller_kyc_grants_seller_product_creation_access(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $profile = $this->createPendingSellerProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseHas('kyc_verifications', [
            'id'          => $kyc->id,
            'status'      => 'approved',
            'verified_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('seller_profiles', [
            'id'                  => $profile->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);
    }

    public function test_approved_seller_can_create_product_after_admin_verification(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createPendingSellerProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);

        $this->assertCanStoreProduct($seller);
    }

    public function test_pending_seller_cannot_create_product(): void
    {
        $seller = $this->createSeller();
        $this->createPendingSellerProfile($seller);

        $this->assertCannotStoreProduct($seller);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'status'  => 'pending',
        ]);
    }

    public function test_rejected_seller_remains_blocked_from_creating_products(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $profile = $this->createPendingSellerProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->actingAs($admin)->post(route('admin.kyc.reject', $kyc), [
            'admin_notes' => 'Invalid documents',
        ])->assertRedirect(route('admin.kyc.index'));

        $this->assertDatabaseHas('kyc_verifications', [
            'id'     => $kyc->id,
            'status' => 'rejected',
        ]);
        $this->assertDatabaseHas('seller_profiles', [
            'id'     => $profile->id,
            'status' => 'pending',
        ]);

        $this->assertCannotStoreProduct($seller);
    }

    public function test_kyc_approval_grants_no_seller_rights_to_customer(): void
    {
        $admin = $this->createAdmin();
        $customer = User::factory()->create(['role_type' => 'customer']);
        $kyc = KycVerification::factory()->create(['user_id' => $customer->id]);

        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseMissing('seller_profiles', ['user_id' => $customer->id]);
        $this->assertDatabaseHas('kyc_verifications', [
            'id'     => $kyc->id,
            'status' => 'approved',
        ]);

        $this->actingAs($customer)->post(route('seller.products.store'), [
            'name'   => 'Customer Product',
            'price'  => 10,
            'status' => 'draft',
        ])->assertStatus(403);
    }

    public function test_reapproving_an_already_approved_seller_keeps_product_access(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $profile = $this->createPendingSellerProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);
        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseHas('seller_profiles', [
            'id'     => $profile->id,
            'status' => 'approved',
        ]);

        $this->assertCanStoreProduct($seller);
    }

    public function test_admin_approval_never_creates_duplicate_role_or_permission_records(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createPendingSellerProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);
        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
    }
}