<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The name of a product is stored in upper case.
 *
 * The rule lives on the model, so every path that writes a product follows
 * it: the seller form, the admin form, the path that lists a supplier
 * product in the store, and any code that creates or updates a product.
 */
class ProductNameNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lowercase_name_is_saved_in_upper_case(): void
    {
        $product = Product::create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'name'      => 'iphone 15 pro max',
            'price'     => 25,
            'status'    => 'published',
        ]);

        $this->assertSame('IPHONE 15 PRO MAX', $product->fresh()->name);
    }

    public function test_a_mixed_case_name_is_saved_in_upper_case(): void
    {
        $product = Product::create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'name'      => 'iPhone 15 Pro Max',
            'price'     => 25,
            'status'    => 'published',
        ]);

        $this->assertSame('IPHONE 15 PRO MAX', $product->fresh()->name);
    }

    public function test_updating_a_product_converts_its_name(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $product->update(['name' => 'new name for the product']);

        $this->assertSame('NEW NAME FOR THE PRODUCT', $product->fresh()->name);
    }

    public function test_the_seller_form_saves_the_name_in_upper_case(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        SellerProfile::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $category = Category::factory()->create(['status' => true]);

        $response = $this->actingAs($seller)->post(route('seller.products.store'), [
            'name'        => 'iphone 15 pro max',
            'price'       => 39.99,
            'status'      => 'published',
            'category_id' => $category->id,
            'description' => 'A test product',
        ]);

        $response->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'name'      => 'IPHONE 15 PRO MAX',
            'seller_id' => $seller->id,
        ]);
    }

    public function test_the_admin_form_saves_the_name_in_upper_case(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);
        $category = Category::factory()->create(['status' => true]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name'        => 'iphone 15 pro max',
            'price'       => 39.99,
            'status'      => 'published',
            'category_id' => $category->id,
            'description' => 'A test product',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name'      => 'IPHONE 15 PRO MAX',
            'seller_id' => $admin->id,
        ]);
    }

    public function test_the_other_fields_are_not_changed(): void
    {
        $product = Product::create([
            'seller_id'         => User::factory()->create(['role_type' => 'seller'])->id,
            'name'              => 'iphone 15 pro max',
            'description'       => 'The description keeps its own case.',
            'short_description' => 'The short description keeps its own case.',
            'sku'               => 'sku-ABC-123',
            'barcode'           => '012345678905',
            'price'             => 25,
            'status'            => 'published',
        ]);

        $fresh = $product->fresh();

        $this->assertSame('The description keeps its own case.', $fresh->description);
        $this->assertSame('The short description keeps its own case.', $fresh->short_description);
        $this->assertSame('sku-ABC-123', $fresh->sku);
        $this->assertSame('012345678905', $fresh->barcode);
        $this->assertSame(25.00, (float) $fresh->price);
    }

    public function test_a_name_in_another_script_is_handled(): void
    {
        $product = Product::create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'name'      => 'café münchen straße',
            'price'     => 25,
            'status'    => 'published',
        ]);

        $this->assertSame(
            'CAFÉ MÜNCHEN STRASSE',
            $product->fresh()->name,
            'The name is converted with a multibyte upper case, which is the upper case of the sharp s.'
        );
    }

    public function test_a_name_that_grows_still_fits_the_column(): void
    {
        // The upper case of "ß" is "SS", so a name at the limit of the column
        // can grow. The name has to stay inside the column.
        $name = str_repeat('ß', 255);

        $product = Product::create([
            'seller_id' => User::factory()->create(['role_type' => 'seller'])->id,
            'name'      => $name,
            'price'     => 25,
            'status'    => 'published',
        ]);

        $stored = $product->fresh()->name;

        $this->assertSame(255, mb_strlen($stored), 'The name must fit the column of the products table.');
        $this->assertSame(str_repeat('S', 255), $stored);
    }

    public function test_the_slug_and_the_relationships_still_work(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'name'        => 'iphone 15 pro max',
            'slug'        => 'iphone-15-pro-max-x1y2z3',
            'price'       => 25,
            'status'      => 'published',
        ]);

        $this->assertSame('IPHONE 15 PRO MAX', $product->fresh()->name);
        $this->assertSame('iphone-15-pro-max-x1y2z3', $product->fresh()->slug, 'The slug is not touched by the name.');

        $this->assertTrue($product->fresh()->seller->is($seller));
        $this->assertTrue($product->fresh()->category->is($category));

        $inventory = Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => 4,
        ]);

        $this->assertTrue($product->fresh()->inventory->is($inventory));
    }

    public function test_the_name_search_still_finds_the_product(): void
    {
        Product::factory()->create([
            'name'    => 'iphone 15 pro max',
            'status'  => 'published',
            'sku'     => 'IPHONE-SKU',
        ]);

        // The search is not case sensitive, so a name that is typed in any
        // case still finds the product.
        $response = $this->get(route('products.search', ['q' => 'iphone']));

        $response->assertStatus(200);
        $response->assertSee('IPHONE 15 PRO MAX');
    }
}
