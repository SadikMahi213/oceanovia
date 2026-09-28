<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerProductUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'seller', 'supplier', 'customer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function createApprovedSeller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'             => $seller->id,
            'status'              => 'approved',
            'verification_status' => 'verified',
        ]);

        return $seller;
    }

    private function productPayload(Category $category): array
    {
        return [
            'name'        => 'Upload Test Product',
            'price'       => 19.99,
            'status'      => 'published',
            'category_id' => $category->id,
            'description' => 'Product used to test upload limits',
        ];
    }

    public function test_product_image_larger_than_per_image_limit_is_rejected(): void
    {
        Storage::fake('public');

        $seller = $this->createApprovedSeller();
        $category = Category::factory()->create(['status' => true]);

        $payload = $this->productPayload($category);
        $payload['images'] = [UploadedFile::fake()->image('oversized.png')->size(3000)];

        $response = $this->actingAs($seller)->post(route('seller.products.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('images.0');
        $this->assertStringContainsString(
            'must not be greater than 2048 kilobytes',
            session('errors')->first('images.0')
        );
        $this->assertDatabaseMissing('products', ['name' => 'Upload Test Product']);
    }

    public function test_valid_product_within_upload_limits_is_created(): void
    {
        Storage::fake('public');

        $seller = $this->createApprovedSeller();
        $category = Category::factory()->create(['status' => true]);

        $payload = $this->productPayload($category);
        $payload['images'] = [
            UploadedFile::fake()->image('thumbnail.png'),
            UploadedFile::fake()->image('secondary.png')->size(1500),
        ];

        $this->actingAs($seller)->post(route('seller.products.store'), $payload)
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'name'      => 'Upload Test Product',
            'seller_id' => $seller->id,
        ]);

        $created = $seller->products()->where('name', 'Upload Test Product')->firstOrFail();
        $this->assertCount(2, $created->images);
    }

    public function test_create_form_exposes_configured_upload_limits_to_seller(): void
    {
        $seller = $this->createApprovedSeller();

        $this->actingAs($seller)->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('Maximum 2 MB per image and 8 MB total.', false)
            ->assertSee('Upload failed because the selected file(s) are too large.', false);
    }
}