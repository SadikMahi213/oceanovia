<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_view_dashboard(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        SupplierProfile::factory()->create([
            'user_id' => $supplier->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($supplier)->get(route('supplier.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Supplier Dashboard');
    }

    public function test_non_supplier_cannot_access_supplier_dashboard(): void
    {
        $customer = User::factory()->create(['role_type' => 'customer']);

        $response = $this->actingAs($customer)->get(route('supplier.dashboard'));

        $response->assertStatus(403);
    }

    public function test_supplier_can_update_inventory(): void
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);
        SupplierProfile::factory()->create([
            'user_id' => $supplier->id,
            'status' => 'approved',
        ]);

        $seller = User::factory()->create(['role_type' => 'seller']);
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'stock_quantity' => 10,
            'stock_alert_threshold' => 3,
        ]);

        $response = $this->actingAs($supplier)->put(route('supplier.inventory.update', $inventory), [
            'stock_quantity' => 25,
            'stock_alert_threshold' => 5,
            'warehouse_location' => 'Aisle 3',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory', [
            'id' => $inventory->id,
            'stock_quantity' => 25,
            'stock_alert_threshold' => 5,
            'warehouse_location' => 'Aisle 3',
        ]);
    }
}
