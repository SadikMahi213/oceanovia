<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_view_dashboard(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create([
            'user_id' => $seller->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($seller)->get(route('seller.products.index'));

        $response->assertStatus(200);
    }

    public function test_seller_can_create_product(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create([
            'user_id' => $seller->id,
            'status' => 'approved',
        ]);
        $category = Category::factory()->create(['status' => true]);

        $response = $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'New Seller Product',
            'price' => 39.99,
            'status' => 'published',
            'category_id' => $category->id,
            'description' => 'A test product',
        ]);

        $response->assertRedirect(route('seller.products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'New Seller Product',
            'seller_id' => $seller->id,
        ]);
    }

    public function test_seller_can_update_product(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create([
            'user_id' => $seller->id,
            'status' => 'approved',
        ]);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seller)->put(route('seller.products.update', $product), [
            'name' => 'Updated Name',
            'price' => 59.99,
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 59.99,
        ]);
    }

    public function test_non_seller_cannot_access_seller_dashboard(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);

        $response = $this->actingAs($customer)->get(route('seller.dashboard'));

        $response->assertStatus(403);
    }
}
