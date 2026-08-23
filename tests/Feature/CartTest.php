<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_to_cart(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 29.99,
        ]);

        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['cart_count', 'total']);
    }

    public function test_auth_user_can_add_to_cart(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 19.99,
        ]);

        $response = $this->actingAs($user)->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_cart_sync_merges_items(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 9.99,
        ]);

        $response = $this->actingAs($user)->post(route('cart.sync'), [
            'items' => [
                ['id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['synced' => true]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orderB = \App\Models\Order::create([
            'user_id' => $userB->id,
            'subtotal' => 100.00,
            'total' => 100.00,
            'status' => 'pending',
        ]);

        // Web guard: GET /orders/{id} should 404 for UserA
        $response = $this->actingAs($userA)->get(route('orders.show', $orderB->id));
        $response->assertStatus(404);

        // API guard: GET /api/orders/{id} should 404 for UserA
        $apiResponse = $this->actingAs($userA)->getJson('/api/orders/' . $orderB->id);
        $apiResponse->assertStatus(404);
    }
}
