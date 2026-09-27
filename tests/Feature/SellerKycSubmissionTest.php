<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\KycVerification;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerKycSubmissionTest extends TestCase
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

    private function submitKycForm(User $seller, array $files = []): \Illuminate\Testing\TestResponse
    {
        $payload = array_merge([
            'document_type'   => 'passport',
            'document_number' => 'AB1234567',
            'document_front'  => UploadedFile::fake()->image('front.png'),
            'document_back'   => UploadedFile::fake()->image('back.png'),
            'selfie'          => UploadedFile::fake()->image('selfie.png'),
        ], $files);

        return $this->actingAs($seller)->post(route('seller.kyc.store'), $payload);
    }

    public function test_seller_can_view_kyc_page(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)->get(route('seller.kyc.index'))->assertOk();
    }

    public function test_customer_cannot_access_seller_kyc(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer', 'email' => 'customer@example.com']);

        $this->actingAs($customer)->get(route('seller.kyc.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('seller.kyc.store'), [])->assertForbidden();
    }

    public function test_seller_submits_kyc_creating_a_pending_review_row(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->submitKycForm($seller)->assertRedirect(route('seller.kyc.index'));

        $this->assertDatabaseCount('kyc_verifications', 1);
        $kyc = KycVerification::where('user_id', $seller->id)->firstOrFail();
        $this->assertSame('passport', $kyc->document_type);
        $this->assertSame('AB1234567', $kyc->document_number);
        $this->assertSame('pending', $kyc->status);
        $this->assertNull($kyc->verified_by);
        $this->assertNull($kyc->verified_at);

        Storage::disk('public')->assertExists($kyc->document_front);
        Storage::disk('public')->assertExists($kyc->document_back);
        Storage::disk('public')->assertExists($kyc->selfie);
    }

    public function test_kyc_submission_requires_document_front(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->actingAs($seller)->post(route('seller.kyc.store'), [
            'document_type'   => 'passport',
            'document_number' => 'AB1234567',
        ])->assertSessionHasErrors(['document_front']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_kyc_submission_validates_document_type(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->actingAs($seller)->post(route('seller.kyc.store'), [
            'document_type'   => 'social_security',
            'document_number' => 'AB1234567',
            'document_front'  => UploadedFile::fake()->image('front.png'),
        ])->assertSessionHasErrors(['document_type']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_rejected_kyc_is_resubmitted_on_the_same_row(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();
        Storage::disk('public')->put('seller-kyc/old-front.jpg', 'old');
        $old = KycVerification::factory()->create([
            'user_id'        => $seller->id,
            'document_front' => 'seller-kyc/old-front.jpg',
            'status'         => 'rejected',
            'admin_notes'    => 'Blurry document, please resubmit.',
        ]);

        $this->submitKycForm($seller)->assertRedirect(route('seller.kyc.index'));

        $this->assertDatabaseCount('kyc_verifications', 1);
        $kyc = $old->fresh();
        $this->assertSame('pending', $kyc->status);
        $this->assertNull($kyc->admin_notes);
        $this->assertNull($kyc->verified_by);
        $this->assertNull($kyc->verified_at);
        $this->assertNotSame('seller-kyc/old-front.jpg', $kyc->document_front);

        Storage::disk('public')->assertMissing('seller-kyc/old-front.jpg');
        Storage::disk('public')->assertExists($kyc->document_front);
    }

    public function test_approved_seller_cannot_submit_kyc_again(): void
    {
        $seller = $this->createSeller();
        KycVerification::factory()->create([
            'user_id' => $seller->id,
            'status'  => 'approved',
        ]);

        $this->submitKycForm($seller)->assertRedirect(route('seller.kyc.index'));

        $this->assertDatabaseCount('kyc_verifications', 1);
        $this->assertSame('approved', KycVerification::firstOrFail()->status);
    }

    public function test_submitted_kyc_shows_in_admin_review_queue(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $seller = $this->createSeller();

        $this->submitKycForm($seller);

        $this->actingAs($admin)->get(route('admin.kyc.index'))
            ->assertOk()
            ->assertSee($seller->name)
            ->assertSee('passport');
    }

    public function test_admin_kyc_show_page_decision_forms_are_not_nested(): void
    {
        $admin = $this->createAdmin();
        $seller = $this->createSeller();
        $kyc = KycVerification::factory()->create(['user_id' => $seller->id]);

        $response = $this->actingAs($admin)->get(route('admin.kyc.show', $kyc));
        $response->assertOk();

        $html = $response->getContent();
        $approveUrl = route('admin.kyc.approve', $kyc);
        $rejectUrl = route('admin.kyc.reject', $kyc);

        $this->assertSame(1, substr_count($html, 'action="'. $approveUrl .'"'));
        $this->assertSame(1, substr_count($html, 'action="'. $rejectUrl .'"'));

        // The approve form must never sit inside the reject form (or vice
        // versa): browsers flatten nested forms so the outer action wins,
        // which made the Approve button submit to the reject endpoint.
        $rejectStart = strpos($html, 'action="'. $rejectUrl .'"');
        $rejectEnd = strpos($html, '</form>', $rejectStart);
        $this->assertStringNotContainsString(
            $approveUrl,
            substr($html, $rejectStart, $rejectEnd - $rejectStart)
        );

        $approveStart = strpos($html, 'action="'. $approveUrl .'"');
        $approveEnd = strpos($html, '</form>', $approveStart);
        $this->assertStringNotContainsString(
            '<form',
            substr($html, $approveStart, $approveEnd - $approveStart)
        );
    }

    public function test_admin_approval_of_submitted_kyc_grants_product_creation(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $seller = $this->createSeller('verified.seller@example.com');

        $this->submitKycForm($seller);

        $kyc = KycVerification::where('user_id', $seller->id)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.kyc.approve', $kyc))
            ->assertRedirect(route('admin.kyc.index'));

        $this->assertDatabaseHas('seller_profiles', [
            'user_id'             => $seller->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);

        $this->assertSame(1, UserNotification::where('notifiable_id', $seller->id)
            ->where('title', 'Congratulations! 🎉 Your seller account has been approved.')
            ->count());

        $category = Category::factory()->create(['status' => true]);
        $this->actingAs($seller->refresh())->post(route('seller.products.store'), [
            'name'        => 'KYC Approved Product',
            'price'       => 19.99,
            'status'      => 'published',
            'category_id' => $category->id,
            'description' => 'Created after seller KYC approval',
        ])->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'name'      => 'KYC Approved Product',
            'seller_id' => $seller->id,
        ]);
    }
}