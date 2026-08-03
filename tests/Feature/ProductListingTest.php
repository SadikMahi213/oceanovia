<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_products_page(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
    }

    public function test_products_page_shows_published_products(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $published = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'name' => 'Visible Product',
        ]);

        $draft = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'name' => 'Hidden Product',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee($published->name);
        $response->assertDontSee($draft->name);
    }

    public function test_product_search_works(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'name' => 'Red Sneakers',
        ]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'name' => 'Blue Hat',
        ]);

        $response = $this->get(route('products.search', ['q' => 'Sneakers']));

        $response->assertStatus(200);
        $response->assertSee('Red Sneakers');
    }

    public function test_product_filter_by_category(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $catA = Category::factory()->create(['status' => true, 'slug' => 'shoes']);
        $catB = Category::factory()->create(['status' => true, 'slug' => 'hats']);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $catA->id,
            'status' => 'published',
            'name' => 'Shoe Product',
        ]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $catB->id,
            'status' => 'published',
            'name' => 'Hat Product',
        ]);

        $response = $this->get(route('products.index', ['category' => 'shoes']));

        $response->assertStatus(200);
        $response->assertSee('Shoe Product');
    }

    public function test_product_detail_page_loads(): void
    {
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => 'published',
            'name' => 'Detail Test Product',
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Detail Test Product');
    }
}
