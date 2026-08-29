<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RecentlyViewed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): User
    {
        return User::factory()->create([
            'role_type' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    public function test_dashboard_loads_when_recently_viewed_product_is_soft_deleted(): void
    {
        $customer = $this->makeCustomer();

        $product = Product::factory()->create(['status' => 'published']);
        RecentlyViewed::create(['user_id' => $customer->id, 'product_id' => $product->id]);

        // Production scenario: product removed from the catalogue (soft delete).
        // The recently_viewed row is NOT cascade-deleted and its product relation becomes null.
        $product->delete();

        $response = $this->actingAs($customer)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_loads_with_valid_recently_viewed_product(): void
    {
        $customer = $this->makeCustomer();

        $product = Product::factory()->create(['status' => 'published']);
        RecentlyViewed::create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $response = $this->actingAs($customer)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    public function test_dashboard_loads_for_customer_with_no_data(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_recently_viewed_page_loads_when_product_is_soft_deleted(): void
    {
        $customer = $this->makeCustomer();

        $product = Product::factory()->create(['status' => 'published']);
        RecentlyViewed::create(['user_id' => $customer->id, 'product_id' => $product->id]);
        $product->delete();

        $response = $this->actingAs($customer)->get('/account/recently-viewed');

        $response->assertStatus(200);
    }
}
