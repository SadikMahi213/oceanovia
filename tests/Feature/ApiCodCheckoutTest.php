<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CodCollection;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCodCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function seller(float $rate = 10.0): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => $rate,
            'status'          => 'approved',
        ]);

        return $seller;
    }

    private function customer(): User
    {
        return User::factory()->create(['role_type' => 'customer']);
    }

    private function addressFor(User $user): Address
    {
        return Address::create([
            'user_id'       => $user->id,
            'address_type'  => 'shipping',
            'first_name'    => 'Jane',
            'last_name'     => 'Doe',
            'phone'         => '1234567890',
            'address_line1' => '456 Oak Ave',
            'city'          => 'New York',
            'state'         => 'NY',
            'zip'           => '10001',
            'country'       => 'US',
        ]);
    }

    private function cartFor(User $customer, Product $product, int $quantity = 1): Address
    {
        $address = $this->addressFor($customer);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $address;
    }

    public function test_api_cod_checkout_creates_collection_and_commission(): void
    {
        $seller = $this->seller(10.0);
        $customer = $this->customer();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);
        $address = $this->cartFor($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $order = Order::firstOrFail();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);

        $this->assertSame(1, Commission::count());
        $this->assertEquals(10.00, (float) Commission::firstOrFail()->amount);

        $collection = CodCollection::firstOrFail();
        $this->assertSame('pending', $collection->status);
        $this->assertSame('seller', $collection->collector_role);
        $this->assertSame($seller->id, $collection->collector_id);
        $this->assertEquals(round((float) $order->total, 2), (float) $collection->expected_amount);

        $this->assertSame(0, Cart::count());
    }

    public function test_api_checkout_rejects_unknown_payment_method(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $product = Product::factory()->create(['seller_id' => $seller->id, 'price' => 10.00, 'status' => 'published']);
        $address = $this->cartFor($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'bitcoin',
        ])->assertStatus(422)->assertJsonValidationErrors('payment_method');

        $this->assertSame(0, Order::count());
    }
}
