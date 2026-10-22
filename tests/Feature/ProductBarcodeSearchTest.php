<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The exact barcode lookup of the product search.
 *
 * The camera scanner puts the scanned code into the existing search field and
 * submits the existing search form, so the barcode arrives as the search term.
 * A barcode is an identity, not a word: it is matched exactly and never as a
 * substring, and it stays a string so that a leading zero survives.
 */
class ProductBarcodeSearchTest extends TestCase
{
    use RefreshDatabase;

    private function publishedProduct(array $attributes = []): Product
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        return Product::factory()->create(array_merge([
            'seller_id'    => $seller->id,
            'category_id'  => $category->id,
            'status'       => 'published',
            'name'         => 'Plain Name Product',
            'description'  => 'A product without a number in its text.',
            'sku'          => null,
            'barcode'      => null,
        ], $attributes));
    }

    public function test_an_exact_barcode_returns_the_product(): void
    {
        $product = $this->publishedProduct([
            'name'    => 'Barcode Product',
            'barcode' => '4006381333931',
        ]);

        $this->publishedProduct(['name' => 'Other Product', 'barcode' => '9780201379624']);

        $response = $this->get(route('products.search', ['q' => '4006381333931']));

        $response->assertStatus(200);
        $response->assertSee('Barcode Product');
        $response->assertDontSee('Other Product');
    }

    public function test_a_partial_barcode_does_not_match(): void
    {
        $this->publishedProduct([
            'name'    => 'Barcode Product',
            'barcode' => '4006381333931',
        ]);

        $response = $this->get(route('products.search', ['q' => '40063813']));

        $response->assertStatus(200);
        $response->assertDontSee('Barcode Product');
        $response->assertSee('No products found');
    }

    public function test_a_longer_code_does_not_match(): void
    {
        $this->publishedProduct([
            'name'    => 'Barcode Product',
            'barcode' => '4006381333931',
        ]);

        $response = $this->get(route('products.search', ['q' => '4006381333931']));

        $response->assertStatus(200);
        $response->assertSee('Barcode Product');
    }

    public function test_the_name_search_is_unchanged(): void
    {
        $this->publishedProduct(['name' => 'Red Sneakers', 'barcode' => '4006381333931']);
        $this->publishedProduct(['name' => 'Blue Hat', 'barcode' => '9780201379624']);

        $response = $this->get(route('products.search', ['q' => 'Sneakers']));

        $response->assertStatus(200);
        $response->assertSee('Red Sneakers');
    }

    public function test_a_barcode_with_a_leading_zero_is_preserved(): void
    {
        $product = $this->publishedProduct([
            'name'    => 'Zero Product',
            'barcode' => '012345678905',
        ]);

        // The stored value keeps its leading zero.
        $this->assertSame('012345678905', $product->fresh()->barcode);

        $response = $this->get(route('products.search', ['q' => '012345678905']));

        $response->assertStatus(200);
        $response->assertSee('Zero Product');

        // Without the leading zero the product is not found: the barcode is
        // an identity, so it is not normalised.
        $withoutZero = $this->get(route('products.search', ['q' => '12345678905']));

        $withoutZero->assertStatus(200);
        $withoutZero->assertDontSee('Zero Product');
    }

    public function test_a_draft_product_is_not_found_by_its_barcode(): void
    {
        $this->publishedProduct([
            'name'    => 'Draft Product',
            'status'  => 'draft',
            'barcode' => '4006381333931',
        ]);

        $response = $this->get(route('products.search', ['q' => '4006381333931']));

        $response->assertStatus(200);
        $response->assertDontSee('Draft Product');
        $response->assertSee('No products found');
    }

    public function test_the_seller_filter_still_applies_to_a_barcode(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $otherSeller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'status'      => 'published',
            'name'        => 'Seller Product',
            'barcode'     => '4006381333931',
        ]);

        Product::factory()->create([
            'seller_id'   => $otherSeller->id,
            'category_id' => $category->id,
            'status'      => 'published',
            'name'        => 'Other Seller Product',
            'barcode'     => '4006381333931',
        ]);

        $response = $this->get(route('products.search', [
            'q'      => '4006381333931',
            'seller' => $seller->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Seller Product');
        $response->assertDontSee('Other Seller Product');
    }

    public function test_an_unknown_barcode_shows_the_no_results_page(): void
    {
        $this->publishedProduct(['name' => 'Some Product', 'barcode' => '4006381333931']);

        $response = $this->get(route('products.search', ['q' => '9999999999999']));

        $response->assertStatus(200);
        $response->assertSee('No products found');
        $response->assertDontSee('Some Product');
    }

    public function test_a_barcode_can_be_shared_by_several_sellers(): void
    {
        // The barcode is not unique: two sellers may carry the same product
        // from the same manufacturer, so both products are found.
        $this->publishedProduct(['name' => 'First Seller Product', 'barcode' => '4006381333931']);
        $this->publishedProduct(['name' => 'Second Seller Product', 'barcode' => '4006381333931']);

        $response = $this->get(route('products.search', ['q' => '4006381333931']));

        $response->assertStatus(200);
        $response->assertSee('First Seller Product');
        $response->assertSee('Second Seller Product');
    }

    public function test_the_api_product_list_also_finds_a_barcode(): void
    {
        $this->publishedProduct(['name' => 'Barcode Product', 'barcode' => '4006381333931']);

        $response = $this->getJson('/api/v1/products?q=4006381333931');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertSame('Barcode Product', $response->json('data.0.name'));
    }

    public function test_a_product_without_a_barcode_is_found_by_its_name(): void
    {
        $this->publishedProduct(['name' => 'Red Sneakers', 'barcode' => null]);

        $response = $this->get(route('products.search', ['q' => 'Red Sneakers']));

        $response->assertStatus(200);
        $response->assertSee('Red Sneakers');
    }

    public function test_the_search_page_offers_the_camera_scanner(): void
    {
        $response = $this->get(route('products.search', ['q' => 'sneakers']));

        $response->assertStatus(200);
        $response->assertSee('Scan a barcode with the camera', false);
        $response->assertSee('barcodeScanner(', false);
    }
}
