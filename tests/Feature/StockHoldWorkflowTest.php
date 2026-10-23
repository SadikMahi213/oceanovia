<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Commission;
use App\Models\CodCollection;
use App\Models\EmailLog;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockHoldWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create(['user_id' => $seller->id, 'commission_rate' => 10.0, 'status' => 'approved']);
        return $seller;
    }

    private function customer(): User
    {
        return User::factory()->create(['role_type' => 'customer']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role_type' => 'admin']);
    }

    private function addressFor(User $user): Address
    {
        return Address::create([
            'user_id' => $user->id, 'address_type' => 'shipping',
            'first_name' => 'John', 'last_name' => 'Doe', 'phone' => '1234567890',
            'address_line1' => '123 Main St', 'city' => 'Los Angeles', 'state' => 'CA',
            'zip' => '90001', 'country' => 'US',
        ]);
    }

    private function productWithStock(User $seller, int $stock, float $price = 25.00): Product
    {
        $product = Product::factory()->create(['seller_id' => $seller->id, 'status' => 'published', 'price' => $price]);
        Inventory::create(['product_id' => $product->id, 'stock_quantity' => $stock]);
        return $product;
    }

    private function fillCart(User $customer, Product $product, int $quantity): Cart
    {
        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $product->price]);
        return $cart;
    }

    public function test_mixed_cart_creates_order_with_held_item(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $inStock = $this->productWithStock($seller, 5, 25.00);
        $oos = $this->productWithStock($seller, 1, 40.00);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $inStock->id, 'quantity' => 2, 'unit_price' => $inStock->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $oos->id, 'quantity' => 3, 'unit_price' => $oos->price]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(2, OrderItem::count());
        $held = OrderItem::where('status', 'awaiting_stock')->firstOrFail();
        $this->assertSame(2, $held->held_quantity); // 3 requested, 1 available
        $confirmed = OrderItem::where('status', 'pending')->firstOrFail();
        $this->assertSame(0, $confirmed->held_quantity);
        $this->assertSame(0, CartItem::count());
    }

    public function test_total_is_confirmed_items_only(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $inStock = $this->productWithStock($seller, 5, 25.00);
        $oos = $this->productWithStock($seller, 0, 40.00);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $inStock->id, 'quantity' => 1, 'unit_price' => $inStock->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $oos->id, 'quantity' => 2, 'unit_price' => $oos->price]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertEquals(25.00, (float) $order->subtotal);
        $this->assertEquals(25.00 + (float)$order->shipping_cost + (float)$order->tax, (float)$order->total);
    }

    public function test_partial_quantity_reserves_available_and_holds_rest(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 3, 10.00);

        $this->fillCart($customer, $product, 5);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $this->assertSame(0, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $item = OrderItem::firstOrFail();
        $this->assertSame(5, $item->quantity);
        $this->assertSame(2, $item->held_quantity);
        $this->assertSame('awaiting_stock', $item->status);
        $this->assertSame(3, $item->confirmedQuantity());
    }

    public function test_all_items_held_creates_zero_total_order(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 2);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertEquals(0.0, (float) $order->total);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(0, CodCollection::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(1, OrderItem::where('status', 'awaiting_stock')->count());
    }

    public function test_held_item_notifies_seller_and_customer(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $item = OrderItem::firstOrFail();
        $this->assertSame(1, EmailLog::where('user_id', $seller->id)->count());
        $this->assertSame(2, EmailLog::where('user_id', $customer->id)->count());
        $this->assertTrue(EmailLog::where('user_id', $seller->id)->first()->idempotency_key === "stock-hold:{$item->id}:seller");
    }

    public function test_duplicate_notification_is_idempotent(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $item = OrderItem::firstOrFail();
        $before = EmailLog::count();
        app(\App\Services\StockHoldService::class)->notifyHeldItem($item);
        $this->assertSame($before, EmailLog::count());
    }

    public function test_wrong_user_cannot_confirm(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();

        $this->actingAs($otherSeller)->post(route('seller.stock.confirm', [$order, $item]))
            ->assertStatus(403);
    }

    public function test_seller_confirm_with_stock_available_confirms_item(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        // restock
        Inventory::where('product_id', $product->id)->update(['stock_quantity' => 5]);

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();

        $this->actingAs($seller)->post(route('seller.stock.confirm', [$order, $item]))
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('pending', $item->status);
        $this->assertSame(0, $item->held_quantity);
        $this->assertNotNull($item->stock_confirmed_at);
        $order->refresh();
        $this->assertEquals(25.00, (float) $order->subtotal);
    }

    public function test_seller_confirm_still_short_keeps_held(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 3);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        Inventory::where('product_id', $product->id)->update(['stock_quantity' => 1]);

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();

        $this->actingAs($seller)->post(route('seller.stock.confirm', [$order, $item]))
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('awaiting_stock', $item->status);
        $this->assertSame(2, $item->held_quantity);
        $this->assertSame(0, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_seller_decline_cancels_item_and_releases_reserved(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 1, 25.00);

        $this->fillCart($customer, $product, 3); // reserve 1, hold 2

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();
        $this->assertSame(0, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);

        $this->actingAs($seller)->post(route('seller.stock.decline', [$order, $item]))
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('cancelled', $item->status);
        $this->assertNotNull($item->stock_declined_at);
        // reserved unit released back
        $this->assertSame(1, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
    }

    public function test_decline_on_paid_order_creates_refund(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 1, 25.00);

        // fully in-stock order first to be paid, then add held item? Hard. Instead test decline with payment_status paid by setting.
        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();
        // make it held + paid artificially
        $item->update(['status' => OrderItem::STATUS_AWAITING_STOCK, 'held_quantity' => 1, 'subtotal' => 25.00]);
        $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);

        $this->actingAs($seller)->post(route('seller.stock.decline', [$order, $item]))
            ->assertRedirect();

        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'order_item_id' => $item->id]);
    }

    public function test_repeated_confirm_decline_idempotent(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        Inventory::where('product_id', $product->id)->update(['stock_quantity' => 5]);

        $order = Order::firstOrFail();
        $item = OrderItem::firstOrFail();

        $this->actingAs($seller)->post(route('seller.stock.confirm', [$order, $item]))->assertRedirect();
        $this->actingAs($seller)->post(route('seller.stock.confirm', [$order, $item]))->assertRedirect(); // no-op

        $item->refresh();
        $this->assertSame('pending', $item->status);
        $this->assertSame(0, $item->held_quantity);
    }

    public function test_customer_notified_of_hold(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 0, 25.00);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id, 'payment_method' => 'cod', 'billing_same' => '1',
        ])->assertRedirect();

        $this->assertSame(1, UserNotification::where('notifiable_id', $customer->id)->count());
    }
}
