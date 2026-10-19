<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\EmailLog;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPlacedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_placed_notifies_customer_and_admin_once(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.0,
            'status'          => 'approved',
        ]);

        $customer = User::factory()->create(['role_type' => 'customer']);
        $admin = User::factory()->create(['role_type' => 'admin']);
        $otherAdmin = User::factory()->create(['role_type' => 'admin']);

        $address = Address::create([
            'user_id'       => $customer->id,
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

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'price'     => 100.00,
            'status'    => 'published',
        ]);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
        ])->assertRedirect();

        $customerLogs = EmailLog::where('user_id', $customer->id)->get();
        $this->assertCount(1, $customerLogs);
        $this->assertStringContainsString('confirmed', $customerLogs->first()->subject);

        $adminLogs = EmailLog::where('user_id', $admin->id)->get();
        $this->assertCount(1, $adminLogs);
        $this->assertStringContainsString('New order', $adminLogs->first()->subject);

        $this->assertSame(0, EmailLog::where('user_id', $otherAdmin->id)->count());
        $this->assertSame(1, EmailLog::whereIn('user_id', [$admin->id, $otherAdmin->id])->count());
    }
}
