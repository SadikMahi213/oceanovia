<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerBusinessKycFieldsTest extends TestCase
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

    private function createSeller(string $email = 'business.seller@example.com'): User
    {
        return User::factory()->create(['role_type' => 'seller', 'email' => $email, 'name' => 'Original Seller Name']);
    }

    private function businessPayload(array $overrides = []): array
    {
        return array_merge([
            'document_type'      => 'passport',
            'document_number'    => 'AB1234567',
            'company_name'       => 'Acme Traders LLC',
            'dba_name'           => 'Acme Store',
            'tax_id'             => '12-3456789',
            'resale_certificate' => UploadedFile::fake()->image('certificate.png'),
            'document_front'     => UploadedFile::fake()->image('front.png'),
            'document_back'      => UploadedFile::fake()->image('back.png'),
            'selfie'             => UploadedFile::fake()->image('selfie.png'),
        ], $overrides);
    }

    private function submitKycForm(User $seller, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($seller)->post(route('seller.kyc.store'), $this->businessPayload($overrides));
    }

    private function makeVerifiedSellerWithDba(): User
    {
        $seller = $this->createSeller();
        SellerProfile::factory()->create([
            'user_id' => $seller->id,
            'status'  => 'approved',
            'store_name' => 'Old Store Name',
        ]);
        KycVerification::factory()->create([
            'user_id'            => $seller->id,
            'status'             => 'approved',
            'company_name'       => 'Acme Traders LLC',
            'dba_name'           => 'Acme Store',
            'tax_id'             => '12-3456789',
            'resale_certificate' => 'kyc/certificate.pdf',
        ]);

        return $seller;
    }

    // ─── Mandatory field validation ─────────────────────────────────────────

    public function test_kyc_form_requires_company_name(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->actingAs($seller)->post(route('seller.kyc.store'), $this->businessPayload(['company_name' => '']))
            ->assertSessionHasErrors(['company_name']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_kyc_form_requires_dba_name(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->actingAs($seller)->post(route('seller.kyc.store'), $this->businessPayload(['dba_name' => '']))
            ->assertSessionHasErrors(['dba_name']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_kyc_form_requires_tax_id(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->actingAs($seller)->post(route('seller.kyc.store'), $this->businessPayload(['tax_id' => '']))
            ->assertSessionHasErrors(['tax_id']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_kyc_form_requires_resale_certificate(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->actingAs($seller)->post(route('seller.kyc.store'), $this->businessPayload(['resale_certificate' => null]))
            ->assertSessionHasErrors(['resale_certificate']);

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    // ─── Submission & persistence ────────────────────────────────────────────

    public function test_seller_submits_business_kyc_fields_and_they_persist(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $this->submitKycForm($seller)->assertRedirect(route('seller.kyc.index'));

        $kyc = KycVerification::where('user_id', $seller->id)->firstOrFail();
        $this->assertSame('Acme Traders LLC', $kyc->company_name);
        $this->assertSame('Acme Store', $kyc->dba_name);
        $this->assertSame('12-3456789', $kyc->tax_id);
        $this->assertNotNull($kyc->resale_certificate);
        $this->assertSame('pending', $kyc->status);

        Storage::disk('public')->assertExists($kyc->resale_certificate);
    }

    public function test_rejected_kyc_resubmission_updates_business_fields_on_same_row(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();
        $old = KycVerification::factory()->create([
            'user_id'            => $seller->id,
            'company_name'       => 'Old Company',
            'dba_name'           => 'Old DBA',
            'tax_id'             => '98-7654321',
            'resale_certificate' => 'kyc/certificate.pdf',
            'status'             => 'rejected',
            'admin_notes'        => 'Please correct your details.',
        ]);

        $this->submitKycForm($seller)->assertRedirect(route('seller.kyc.index'));

        $this->assertDatabaseCount('kyc_verifications', 1);
        $kyc = $old->fresh();
        $this->assertSame('Acme Traders LLC', $kyc->company_name);
        $this->assertSame('Acme Store', $kyc->dba_name);
        $this->assertSame('12-3456789', $kyc->tax_id);
        $this->assertSame('pending', $kyc->status);
        $this->assertNull($kyc->admin_notes);
    }

    public function test_resubmission_replaces_old_resale_certificate_file(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();
        Storage::disk('public')->put('seller-kyc/old-cert.pdf', 'old');
        KycVerification::factory()->create([
            'user_id'            => $seller->id,
            'document_front'     => 'seller-kyc/old-front.jpg',
            'resale_certificate' => 'seller-kyc/old-cert.pdf',
            'status'             => 'rejected',
        ]);

        $this->submitKycForm($seller);

        $kyc = KycVerification::where('user_id', $seller->id)->firstOrFail();
        $this->assertNotSame('seller-kyc/old-cert.pdf', $kyc->resale_certificate);
        Storage::disk('public')->assertMissing('seller-kyc/old-cert.pdf');
        Storage::disk('public')->assertExists($kyc->resale_certificate);
    }

    // ─── Admin review ────────────────────────────────────────────────────────

    public function test_admin_sees_business_fields_on_kyc_show_page(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $seller = $this->createSeller();

        $this->submitKycForm($seller);

        $kyc = KycVerification::where('user_id', $seller->id)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.kyc.show', $kyc))
            ->assertOk()
            ->assertSee('Acme Traders LLC')
            ->assertSee('Acme Store')
            ->assertSee('12-3456789')
            ->assertSee(basename($kyc->resale_certificate));
    }

    public function test_admin_approval_keeps_business_fields_and_grants_profile(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $seller = $this->createSeller();

        $this->submitKycForm($seller);

        $kyc = KycVerification::where('user_id', $seller->id)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.kyc.approve', $kyc))
            ->assertRedirect(route('admin.kyc.index'));

        $this->assertDatabaseHas('seller_profiles', [
            'user_id'             => $seller->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);

        $fresh = $kyc->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame('Acme Traders LLC', $fresh->company_name);
        $this->assertSame('Acme Store', $fresh->dba_name);
        $this->assertSame('12-3456789', $fresh->tax_id);
    }

    // ─── Public display (approved DBA) ──────────────────────────────────────

    public function test_public_product_listing_shows_approved_dba_name(): void
    {
        $seller = $this->makeVerifiedSellerWithDba();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'DBA Display Product',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('DBA Display Product')
            ->assertSee('Acme Store')
            ->assertDontSee('Old Store Name');
    }

    public function test_public_product_listing_hides_private_kyc_data(): void
    {
        $seller = $this->makeVerifiedSellerWithDba();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'Privacy Product',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Privacy Product')
            ->assertDontSee('12-3456789')
            ->assertDontSee('Acme Traders LLC')
            ->assertDontSee('certificate.pdf');
    }

    public function test_product_detail_page_shows_dba_and_hides_private_data(): void
    {
        $seller = $this->makeVerifiedSellerWithDba();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'DBA Detail Product',
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('DBA Detail Product')
            ->assertSee('Acme Store')
            ->assertDontSee('12-3456789')
            ->assertDontSee('Acme Traders LLC')
            ->assertDontSee('certificate.pdf');
    }

    // ─── API display ────────────────────────────────────────────────────────

    public function test_api_product_index_exposes_dba_not_private_kyc_data(): void
    {
        $seller = $this->makeVerifiedSellerWithDba();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'API Index Product',
        ]);

        $response = $this->getJson('/api/v1/products')->assertOk();

        $this->assertSame('Acme Store', $response->json('data.0.seller'));
        $content = $response->getContent();
        $this->assertStringNotContainsString('12-3456789', $content);
        $this->assertStringNotContainsString('Acme Traders LLC', $content);
        $this->assertStringNotContainsString('certificate.pdf', $content);
    }

    public function test_api_product_show_exposes_dba_not_private_kyc_data(): void
    {
        $seller = $this->makeVerifiedSellerWithDba();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'API Show Product',
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->id)->assertOk();

        $this->assertSame('Acme Store', $response->json('data.seller.name'));
        $content = $response->getContent();
        $this->assertStringNotContainsString('12-3456789', $content);
        $this->assertStringNotContainsString('Acme Traders LLC', $content);
        $this->assertStringNotContainsString('certificate.pdf', $content);
    }
}