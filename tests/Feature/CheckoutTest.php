<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_checkout(): void
    {
        $response = $this->get(route('checkout.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_auth_user_can_checkout_with_cod(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 49.99,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'address_type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'cod',
        ]);
    }

    public function test_order_created_with_correct_items(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'price' => 25.00,
            'name' => 'Order Item Test',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price,
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'address_type' => 'shipping',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '9876543210',
            'address_line1' => '456 Oak Ave',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_name' => 'Order Item Test',
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
        ]);
    }
}
