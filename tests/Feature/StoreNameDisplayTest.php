<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerPayout;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreNameDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function sellerWithStore(string $storeName, string $ownerName = 'Owner Person'): User
    {
        $seller = User::factory()->create([
            'role_type' => 'seller',
            'name'      => $ownerName,
            'status'    => 'active',
        ]);

        SellerProfile::factory()->create([
            'user_id'    => $seller->id,
            'store_name' => $storeName,
            'status'     => 'approved',
        ]);

        return $seller;
    }

    private function makeOrder(User $customer, User $seller): Order
    {
        $order = Order::create([
            'user_id'  => $customer->id,
            'subtotal' => 25.00,
            'total'    => 25.00,
            'status'   => 'pending',
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'seller_id'    => $seller->id,
            'product_name' => 'Widget',
            'quantity'     => 1,
            'unit_price'   => 25.00,
            'subtotal'     => 25.00,
        ]);

        return $order;
    }

    public function test_customer_order_page_shows_store_name_not_account_name(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);
        $seller = $this->sellerWithStore('Bright Star Store', 'Jane Owner');
        $order = $this->makeOrder($customer, $seller);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Bright Star Store')
            ->assertDontSee('Jane Owner');
    }

    public function test_admin_order_page_shows_store_name(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin']);
        $customer = User::factory()->create(['role_type' => 'customer']);
        $seller = $this->sellerWithStore('Bright Star Store', 'Jane Owner');
        $order = $this->makeOrder($customer, $seller);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Bright Star Store');
    }

    public function test_admin_products_index_shows_store_name(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin']);
        $seller = $this->sellerWithStore('Bright Star Store');

        Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Bright Star Store');
    }

    public function test_admin_payouts_index_shows_store_name(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin']);
        $seller = $this->sellerWithStore('Bright Star Store');

        SellerPayout::create([
            'seller_id'      => $seller->id,
            'amount'         => 100.00,
            'fee'            => 0,
            'net_amount'     => 100.00,
            'payment_method' => 'bank',
            'status'         => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payouts.index'))
            ->assertOk()
            ->assertSee('Bright Star Store');
    }

    public function test_public_listing_falls_back_to_dba_when_store_name_empty(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller', 'name' => 'Account Owner']);

        SellerProfile::factory()->create([
            'user_id'    => $seller->id,
            'store_name' => '',
            'status'     => 'approved',
        ]);

        KycVerification::factory()->create([
            'user_id'  => $seller->id,
            'status'   => 'approved',
            'dba_name' => 'Fallback DBA Name',
        ]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'Fallback Product',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Fallback DBA Name');
    }

    public function test_public_listing_falls_back_to_account_name_without_profile(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller', 'name' => 'Lonely Account']);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'name'      => 'No Profile Product',
        ]);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Lonely Account');
    }
}
