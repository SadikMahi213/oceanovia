<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The checkout confirmation must describe the persisted order state.
 *
 * Placing an order and settling its payment are separate things: a cash on
 * delivery order is placed with its payment still pending, and an unpaid card
 * order must never be presented as a confirmed payment.
 */
class CheckoutSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(
        User $customer,
        string $paymentMethod = 'cod',
        string $paymentStatus = 'pending',
        string $status = 'confirmed'
    ): Order {
        $product = Product::factory()->create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'status'    => 'published',
            'price'     => 25.00,
        ]);

        $order = Order::create([
            'user_id'        => $customer->id,
            'subtotal'       => 25.00,
            'total'          => 25.00,
            'status'         => $status,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $product->seller_id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => 25.00,
            'subtotal'     => 25.00,
            'status'       => $status,
        ]);

        return $order->fresh('items');
    }

    public function test_cod_order_with_pending_payment_gets_an_order_placement_confirmation(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($customer, 'cod', 'pending', 'confirmed');

        $response = $this->actingAs($customer)->get(route('checkout.success', $order));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Order Placed', $html);
        $this->assertStringContainsString('Payment Pending', $html);
        $this->assertStringContainsString('Payment will be collected when your order is delivered.', $html);

        // No false payment-success claim.
        $this->assertStringNotContainsString('Order Placed Successfully!', $html);
        $this->assertStringNotContainsString('Your payment has been confirmed', $html);
    }

    public function test_paid_order_keeps_the_payment_success_confirmation(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($customer, 'stripe', 'paid', 'confirmed');

        $response = $this->actingAs($customer)->get(route('checkout.success', $order));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Order Placed Successfully!', $html);
        $this->assertStringContainsString('Your payment has been confirmed', $html);
        $this->assertStringNotContainsString('Payment Pending', $html);
    }

    public function test_unpaid_card_order_is_not_shown_as_payment_confirmed(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($customer, 'stripe', 'pending', 'pending');

        $response = $this->actingAs($customer)->get(route('checkout.success', $order));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('Order Placed Successfully!', $html);
        $this->assertStringNotContainsString('Your payment has been confirmed', $html);
        $this->assertStringContainsString('your payment has not been confirmed yet', $html);
    }

    public function test_cancelled_order_is_redirected_away_from_the_success_page(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($customer, 'cod', 'pending', 'cancelled');

        $response = $this->actingAs($customer)->get(route('checkout.success', $order));

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('cancelled', session('error'));
    }

    public function test_refunded_order_is_redirected_away_from_the_success_page(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($customer, 'stripe', 'refunded', 'refunded');

        $response = $this->actingAs($customer)->get(route('checkout.success', $order));

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('refunded', session('error'));
    }

    public function test_another_customers_order_remains_inaccessible(): void
    {
        $owner = User::factory()->create(['role_type' => 'customer']);
        $intruder = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($owner, 'cod', 'pending', 'confirmed');

        $this->actingAs($intruder)
            ->get(route('checkout.success', $order))
            ->assertForbidden();
    }

    public function test_guest_cannot_reach_the_success_page(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $order = $this->orderFor($customer, 'cod', 'pending', 'confirmed');

        $this->get(route('checkout.success', $order))->assertRedirect(route('login'));
    }

    public function test_cod_checkout_still_confirms_order_placement_on_the_order_page(): void
    {
        $user = User::factory()->create(['role_type' => 'customer']);
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'status'      => 'published',
            'price'       => 25.00,
        ]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 10]);

        $address = Address::create([
            'user_id'       => $user->id,
            'address_type'  => 'shipping',
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'phone'         => '1234567890',
            'address_line1' => '123 Main St',
            'address_line2' => 'Apt 4B',
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');
        $this->assertSame(
            'Order placed successfully! Payment will be collected on delivery.',
            session('success')
        );

        // The order is placed, its payment is not settled.
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }
}
