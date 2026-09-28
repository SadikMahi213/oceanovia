<?php

namespace Tests\Feature;

use App\Jobs\SendAdminEmail;
use App\Models\Category;
use App\Models\EmailLog;
use App\Models\KycVerification;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'seller', 'supplier', 'customer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role_type' => 'admin']);
    }

    private function createSeller(string $email = 'seller@example.com'): User
    {
        return User::factory()->create(['role_type' => 'seller', 'email' => $email]);
    }

    private function registerSeller(): User
    {
        $payload = [
            'name' => 'Test Seller',
            'email' => 'registered.seller@example.com',
            'password' => 'Passw0rd!12',
            'password_confirmation' => 'Passw0rd!12',
            'role_type' => 'seller',
        ];

        $this->post('/register', $payload)->assertStatus(302);

        return User::where('email', $payload['email'])->firstOrFail();
    }

    private function createPendingProfile(User $seller): SellerProfile
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
            'name'        => 'Verified Seller Product',
            'price'       => 29.99,
            'status'      => 'published',
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
            'name'        => 'Blocked Product',
            'price'       => 29.99,
            'status'      => 'published',
            'category_id' => $category->id,
            'description' => 'A product that must not be created',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('products', [
            'name'      => 'Blocked Product',
            'seller_id' => $seller->id,
        ]);
    }

    public function test_seller_registered_through_register_endpoint_gets_pending_seller_profile(): void
    {
        $seller = $this->registerSeller();

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'status'  => 'pending',
        ]);
    }

    public function test_registered_seller_cannot_add_products_before_approval(): void
    {
        $seller = $this->registerSeller();

        $this->assertCannotStoreProduct($seller);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'status'  => 'pending',
        ]);
    }

    public function test_admin_approval_grants_registered_seller_product_creation_access(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->registerSeller();
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id'             => $seller->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);

        $this->assertCanStoreProduct($seller);
    }

    public function test_admin_approval_creates_seller_profile_for_seller_without_one(): void
    {
        // Historical bad state: a seller registered before the fix has no
        // seller profile at all, so approval used to grant nothing.
        $admin = $this->createAdmin();
        $seller = $this->createSeller('legacy.seller@example.com');
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->assertDatabaseMissing('seller_profiles', ['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id'             => $seller->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);

        $this->assertCanStoreProduct($seller);
    }

    public function test_unapproved_registered_seller_stays_blocked(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createPendingProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->assertCannotStoreProduct($seller);

        $this->actingAs($admin)->post(route('admin.kyc.reject', $kyc), [
            'admin_notes' => 'Documents pending review',
        ])->assertRedirect(route('admin.kyc.index'));

        $this->assertCannotStoreProduct($seller);
    }

    public function test_admin_user_index_displays_readable_role_labels(): void
    {
        $admin = $this->createAdmin();
        User::factory()->create(['role_type' => 'seller']);
        User::factory()->create(['role_type' => 'supplier']);
        User::factory()->create(['role_type' => 'customer']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Seller');
        $response->assertSee('Supplier');
        $response->assertSee('Customer');
    }

    public function test_admin_can_suspend_user_and_account_is_blocked(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();

        $this->actingAs($admin)->patch(route('admin.users.update-status', $seller), [
            'status' => 'suspended',
            'reason' => 'Policy violation',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'     => $seller->id,
            'status' => 'suspended',
        ]);

        // The guard user must be a fresh object so EnsureAccountStatus sees
        // the updated status (in production the user is reloaded per request).
        $seller->refresh();

        $this->actingAs($seller)->get(route('seller.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_deactivate_user(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();

        $this->actingAs($admin)->patch(route('admin.users.update-status', $seller), [
            'status' => 'inactive',
            'reason' => 'Store on hold',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'     => $seller->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_user_and_revoke_session_tokens(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $seller->createToken('mobile-app');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $seller))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $seller->id]);
        $this->assertSame(0, User::withTrashed()->find($seller->id)?->tokens()->count());
    }

    public function test_kyc_approval_creates_exactly_one_congratulations_notification(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createPendingProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);
        $this->approveKyc($admin, $kyc);

        $this->assertSame(1, UserNotification::where('notifiable_id', $seller->id)
            ->where('title', 'Congratulations! 🎉 Your seller account has been approved.')
            ->count());
    }

    public function test_approving_twice_on_same_instance_never_creates_duplicate_profiles(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->actingAs($admin);

        $controller = app(\App\Http\Controllers\Admin\KycVerificationController::class);
        $audit = app(\App\Services\AuditService::class);
        $controller->approve($kyc, $audit);
        $controller->approve($kyc, $audit);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id'             => $seller->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);
        $this->assertSame(1, SellerProfile::where('user_id', $seller->id)->count());
        $this->assertSame(1, UserNotification::where('notifiable_id', $seller->id)->count());
    }

    public function test_verifying_a_user_creates_congratulations_notification_once(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role_type' => 'customer', 'email_verified_at' => null]);

        $this->actingAs($admin)->patch(route('admin.users.verify', $user))
            ->assertSessionHas('success');

        $this->assertSame(1, UserNotification::where('notifiable_id', $user->id)
            ->where('title', 'Congratulations! 🎉 Your account has been verified.')
            ->count());

        $this->actingAs($admin)->patch(route('admin.users.verify', $user))
            ->assertSessionHas('info');

        $this->assertSame(1, UserNotification::where('notifiable_id', $user->id)
            ->where('title', 'Congratulations! 🎉 Your account has been verified.')
            ->count());
    }

    public function test_kyc_approval_queues_approval_email_through_existing_pipeline(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $this->createPendingProfile($seller);
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $this->approveKyc($admin, $kyc);
        $this->approveKyc($admin, $kyc);

        $log = EmailLog::where('user_id', $seller->id)->firstOrFail();
        $this->assertSame('Oceanovia — Congratulations! Your Account Has Been Approved', $log->subject);
        $this->assertSame('requested', $log->status);
        $this->assertSame('text', $log->body_type);
        $this->assertStringStartsWith('Hello '.$seller->name.',', $log->message);
        $this->assertStringContainsString('Congratulations! Your seller account has been approved on Oceanovia.com.', $log->message);
        $this->assertStringContainsString('You can now add and publish products, manage inventory and pricing, and track orders and payouts.', $log->message);
        $this->assertStringContainsString('If you have any questions, reply to support@oceanovia.com.', $log->message);
        $this->assertStringContainsString("Thank you for choosing\nOceanovia.com.\n\"Where Business Flows\"", $log->message);

        Queue::assertPushed(SendAdminEmail::class, function (SendAdminEmail $job) use ($log) {
            return $job->emailLog->is($log);
        });

        $this->assertSame(1, EmailLog::where('user_id', $seller->id)->count());
    }

    public function test_customer_kyc_approval_creates_no_seller_notification_or_email(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();
        $customer = User::factory()->create(['role_type' => 'customer', 'email' => 'customer@example.com']);
        $kyc = KycVerification::factory()->create(['user_id' => $customer->id]);

        $this->approveKyc($admin, $kyc);

        $this->assertDatabaseMissing('user_notifications', ['notifiable_id' => $customer->id]);
        $this->assertSame(0, EmailLog::where('user_id', $customer->id)->count());
        Queue::assertNotPushed(SendAdminEmail::class);
    }
}