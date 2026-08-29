<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellerProfile;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Users ──────────────────────────────────────────────────────────

        $admin = User::firstOrCreate(
            ['email' => 'admin@oceanovia.com'],
            [
                'name'              => 'Admin',
                'lastname'          => 'User',
                'username'          => 'admin',
                'phone'             => '+1 (555) 000-0001',
                'password'          => 'Password@123',
                'role_type'         => 'admin',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $seller = User::firstOrCreate(
            ['email' => 'seller@oceanovia.com'],
            [
                'name'              => 'John',
                'lastname'          => 'Seller',
                'username'          => 'johnseller',
                'phone'             => '+1 (555) 000-0002',
                'password'          => 'Password@123',
                'role_type'         => 'seller',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $supplier = User::firstOrCreate(
            ['email' => 'supplier@oceanovia.com'],
            [
                'name'              => 'Jane',
                'lastname'          => 'Supplier',
                'username'          => 'janesupplier',
                'phone'             => '+1 (555) 000-0003',
                'password'          => 'Password@123',
                'role_type'         => 'supplier',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@oceanovia.com'],
            [
                'name'              => 'Bob',
                'lastname'          => 'Customer',
                'username'          => 'bobcustomer',
                'phone'             => '+1 (555) 000-0004',
                'password'          => 'Password@123',
                'role_type'         => 'customer',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
        if (! $seller->hasRole('seller')) {
            $seller->assignRole('seller');
        }
        if (! $supplier->hasRole('supplier')) {
            $supplier->assignRole('supplier');
        }
        if (! $customer->hasRole('customer')) {
            $customer->assignRole('customer');
        }

        // ─── Profiles ───────────────────────────────────────────────────────

        SellerProfile::firstOrCreate(
            ['user_id' => $seller->id],
            [
                'store_name'      => 'John\'s Gadgets',
                'store_slug'      => 'johns-gadgets',
                'description'     => 'Your one-stop shop for the latest gadgets and electronics.',
                'address'         => '123 Tech Street, San Francisco, CA 94102',
                'phone'           => '+1 (555) 100-2000',
                'status'          => 'approved',
                'commission_rate' => 10.00,
            ]
        );

        SupplierProfile::firstOrCreate(
            ['user_id' => $supplier->id],
            [
                'company_name' => 'Jane\'s Wholesale',
                'company_slug' => 'janes-wholesale',
                'description'  => 'Wholesale supplier of electronics and accessories.',
                'address'      => '456 Industry Blvd, Los Angeles, CA 90001',
                'phone'        => '+1 (555) 300-4000',
                'status'       => 'approved',
            ]
        );

        // ─── Categories ─────────────────────────────────────────────────────

        $categories = [
            ['name' => 'Electronics', 'description' => 'Gadgets, devices, and electronic accessories', 'is_featured' => true, 'sort_order' => 1, 'status' => true],
            ['name' => 'Clothing', 'description' => 'Fashion apparel and accessories for men and women', 'is_featured' => true, 'sort_order' => 2, 'status' => true],
            ['name' => 'Home & Kitchen', 'description' => 'Everything for your home and kitchen needs', 'is_featured' => true, 'sort_order' => 3, 'status' => true],
            ['name' => 'Books', 'description' => 'Books across all genres', 'sort_order' => 4, 'status' => true],
            ['name' => 'Sports & Outdoors', 'description' => 'Sports equipment and outdoor gear', 'is_featured' => true, 'sort_order' => 5, 'status' => true],
            ['name' => 'Health & Beauty', 'description' => 'Personal care and beauty products', 'sort_order' => 6, 'status' => true],
            ['name' => 'Toys & Games', 'description' => 'Toys for all ages', 'sort_order' => 7, 'status' => true],
            ['name' => 'Automotive', 'description' => 'Car accessories and automotive parts', 'sort_order' => 8, 'status' => true],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $slug = (string) str($cat['name'])->slug();
            $c = Category::firstOrCreate(
                ['slug' => $slug],
                $cat
            );
            $categoryIds[$slug] = $c->id;
        }

        // ─── Sub-categories ─────────────────────────────────────────────────

        $subCategories = [
            ['parent_slug' => 'electronics', 'name' => 'Smartphones', 'sort_order' => 1, 'status' => true],
            ['parent_slug' => 'electronics', 'name' => 'Laptops', 'sort_order' => 2, 'status' => true],
            ['parent_slug' => 'electronics', 'name' => 'Headphones', 'sort_order' => 3, 'status' => true],
            ['parent_slug' => 'clothing', 'name' => 'Men', 'sort_order' => 1, 'status' => true],
            ['parent_slug' => 'clothing', 'name' => 'Women', 'sort_order' => 2, 'status' => true],
            ['parent_slug' => 'home-kitchen', 'name' => 'Furniture', 'sort_order' => 1, 'status' => true],
            ['parent_slug' => 'home-kitchen', 'name' => 'Kitchen Appliances', 'sort_order' => 2, 'status' => true],
        ];

        foreach ($subCategories as $sub) {
            Category::firstOrCreate(
                ['slug' => str($sub['name'])->slug()],
                [
                    'parent_id'   => $categoryIds[$sub['parent_slug']] ?? null,
                    'name'        => $sub['name'],
                    'sort_order'  => $sub['sort_order'],
                    'status'      => $sub['status'],
                ]
            );
        }

        // ─── Products ───────────────────────────────────────────────────────

        $productsData = [
            [
                'category_slug' => 'smartphones',
                'name' => 'Wireless Bluetooth Headphones Pro',
                'description' => 'Premium wireless headphones with active noise cancellation, 30-hour battery life, and comfortable over-ear design. Features high-fidelity audio and built-in microphone for calls.',
                'price' => 149.99,
                'compare_price' => 199.99,
                'sku' => 'WBH-001',
                'tags' => ['bluetooth', 'wireless', 'headphones', 'audio'],
                'colors' => ['Black', 'White', 'Navy'],
                'sizes' => ['Standard'],
                'is_featured' => true,
                'stock' => 50,
            ],
            [
                'category_slug' => 'smartphones',
                'name' => 'Smartphone Stand Wireless Charger',
                'description' => 'Fast wireless charging stand compatible with all Qi-enabled devices. Adjustable viewing angle with LED charging indicator.',
                'price' => 39.99,
                'compare_price' => 49.99,
                'sku' => 'SSC-002',
                'tags' => ['charger', 'wireless', 'smartphone', 'stand'],
                'colors' => ['Black', 'White'],
                'sizes' => ['Standard'],
                'is_featured' => true,
                'stock' => 100,
            ],
            [
                'category_slug' => 'laptops',
                'name' => 'Ultra-Thin Laptop Sleeve 13"',
                'description' => 'Sleek and protective laptop sleeve made from premium neoprene. Fits 13-inch laptops with extra padding for drop protection.',
                'price' => 29.99,
                'compare_price' => null,
                'sku' => 'ULS-003',
                'tags' => ['laptop', 'sleeve', 'case', 'protection'],
                'colors' => ['Gray', 'Black', 'Blue'],
                'sizes' => ['13-inch'],
                'is_featured' => false,
                'stock' => 75,
            ],
            [
                'category_slug' => 'headphones',
                'name' => 'USB-C Wired Headphones',
                'description' => 'High-quality wired headphones with USB-C connectivity. Features inline microphone and volume control.',
                'price' => 24.99,
                'compare_price' => 34.99,
                'sku' => 'UWH-004',
                'tags' => ['headphones', 'usb-c', 'wired', 'audio'],
                'colors' => ['Black'],
                'sizes' => ['Standard'],
                'is_featured' => false,
                'stock' => 120,
            ],
            [
                'category_slug' => 'men',
                'name' => 'Classic Fit Cotton T-Shirt',
                'description' => 'Premium 100% organic cotton t-shirt with a classic fit. Pre-shrunk fabric with reinforced stitching for long-lasting wear.',
                'price' => 19.99,
                'compare_price' => 24.99,
                'sku' => 'CTS-005',
                'tags' => ['t-shirt', 'cotton', 'classic', 'casual'],
                'colors' => ['White', 'Black', 'Navy', 'Gray'],
                'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                'is_featured' => true,
                'stock' => 200,
            ],
            [
                'category_slug' => 'women',
                'name' => 'Yoga Leggings High-Waist',
                'description' => 'Buttery-soft high-waist yoga leggings with 4-way stretch. Moisture-wicking fabric with hidden waistband pocket.',
                'price' => 44.99,
                'compare_price' => 59.99,
                'sku' => 'YGL-006',
                'tags' => ['yoga', 'leggings', 'workout', 'fitness'],
                'colors' => ['Black', 'Navy', 'Purple', 'Pink'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
                'is_featured' => true,
                'stock' => 150,
            ],
            [
                'category_slug' => 'home-kitchen',
                'name' => 'Stainless Steel Water Bottle 32oz',
                'description' => 'Double-wall vacuum insulated water bottle. Keeps drinks cold for 24 hours or hot for 12 hours. BPA-free with leak-proof lid.',
                'price' => 34.99,
                'compare_price' => null,
                'sku' => 'SWB-007',
                'tags' => ['water bottle', 'stainless', 'insulated', 'eco-friendly'],
                'colors' => ['Silver', 'Black', 'White', 'Green'],
                'sizes' => ['32oz'],
                'is_featured' => true,
                'stock' => 180,
            ],
            [
                'category_slug' => 'kitchen-appliances',
                'name' => 'Digital Kitchen Scale',
                'description' => 'Precision digital kitchen scale with 0.1g accuracy. Features tare function, auto-off, and LCD display. Measures up to 5kg.',
                'price' => 22.99,
                'compare_price' => 29.99,
                'sku' => 'DKS-008',
                'tags' => ['kitchen', 'scale', 'digital', 'cooking'],
                'colors' => ['Silver', 'Black'],
                'sizes' => ['Standard'],
                'is_featured' => false,
                'stock' => 60,
            ],
            [
                'category_slug' => 'books',
                'name' => 'The Art of Clean Code',
                'description' => 'A comprehensive guide to writing maintainable and readable code. Covers best practices, design patterns, and refactoring techniques.',
                'price' => 39.99,
                'compare_price' => 49.99,
                'sku' => 'BK-009',
                'tags' => ['book', 'coding', 'programming', 'software'],
                'is_featured' => true,
                'stock' => 90,
            ],
            [
                'category_slug' => 'sports-outdoors',
                'name' => 'Adjustable Dumbbell Set 50lb',
                'description' => 'Space-saving adjustable dumbbell set ranging from 5 to 50 pounds. Quick-change weight selection with durable steel construction.',
                'price' => 199.99,
                'compare_price' => 249.99,
                'sku' => 'ADS-010',
                'tags' => ['dumbbell', 'fitness', 'weight', 'gym'],
                'is_featured' => true,
                'stock' => 30,
            ],
        ];

        foreach ($productsData as $data) {
            $categorySlug = $data['category_slug'];
            unset($data['category_slug']);
            $stock = $data['stock'] ?? 0;
            unset($data['stock']);

            $catId = $categoryIds[$categorySlug] ?? null;

            $slug = (string) str($data['name'])->slug() . '-' . substr(md5($data['sku']), 0, 6);
            $product = Product::firstOrCreate(
                ['sku' => $data['sku']],
                array_merge($data, [
                    'seller_id'    => $seller->id,
                    'category_id'  => $catId,
                    'slug'         => $slug,
                    'status'       => 'published',
                    'images'       => [],
                    'short_description' => substr($data['description'], 0, 100),
                    'weight'       => 0.5,
                ])
            );

            Inventory::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'supplier_id'           => $supplier->id,
                    'stock_quantity'        => $stock,
                    'stock_alert_threshold' => 5,
                    'warehouse_location'    => 'Warehouse A',
                ]
            );
        }

        // ─── Product Variants ───────────────────────────────────────────────

        $tshirt = Product::where('sku', 'CTS-005')->first();
        if ($tshirt && $tshirt->variants()->count() === 0) {
            $sizeColors = [
                ['S', 'White', 10], ['S', 'Black', 8], ['S', 'Navy', 5],
                ['M', 'White', 15], ['M', 'Black', 12], ['M', 'Navy', 10], ['M', 'Gray', 7],
                ['L', 'White', 20], ['L', 'Black', 18], ['L', 'Navy', 14], ['L', 'Gray', 10],
                ['XL', 'White', 12], ['XL', 'Black', 10], ['XL', 'Navy', 8],
                ['XXL', 'White', 8], ['XXL', 'Black', 6],
            ];
            foreach ($sizeColors as $i => [$size, $color, $qty]) {
                ProductVariant::create([
                    'product_id'     => $tshirt->id,
                    'sku'            => "CTS-005-{$size}-{$color}",
                    'price'          => null,
                    'stock_quantity' => $qty,
                    'color'          => $color,
                    'size'           => $size,
                    'is_active'      => true,
                    'sort_order'     => $i + 1,
                ]);
            }
        }

        $leggings = Product::where('sku', 'YGL-006')->first();
        if ($leggings && $leggings->variants()->count() === 0) {
            $sizeColors = [
                ['XS', 'Black', 8], ['XS', 'Navy', 5],
                ['S', 'Black', 12], ['S', 'Navy', 10], ['S', 'Purple', 6],
                ['M', 'Black', 15], ['M', 'Navy', 12], ['M', 'Purple', 8], ['M', 'Pink', 6],
                ['L', 'Black', 10], ['L', 'Navy', 8], ['L', 'Purple', 5],
                ['XL', 'Black', 6],
            ];
            foreach ($sizeColors as $i => [$size, $color, $qty]) {
                ProductVariant::create([
                    'product_id'     => $leggings->id,
                    'sku'            => "YGL-006-{$size}-{$color}",
                    'price'          => null,
                    'stock_quantity' => $qty,
                    'color'          => $color,
                    'size'           => $size,
                    'is_active'      => true,
                    'sort_order'     => $i + 1,
                ]);
            }
        }

        // ─── Addresses ─────────────────────────────────────────────────────

        $addresses = [
            [
                'user_id'       => $customer->id,
                'address_type'  => 'shipping',
                'first_name'    => 'Bob',
                'last_name'     => 'Customer',
                'phone'         => '+1 (555) 111-2222',
                'address_line1' => '123 Main Street',
                'address_line2' => 'Apt 4B',
                'city'          => 'New York',
                'state'         => 'NY',
                'zip'           => '10001',
                'country'       => 'US',
                'is_default'    => true,
            ],
            [
                'user_id'       => $customer->id,
                'address_type'  => 'billing',
                'first_name'    => 'Bob',
                'last_name'     => 'Customer',
                'phone'         => '+1 (555) 111-2222',
                'address_line1' => '123 Main Street',
                'address_line2' => 'Apt 4B',
                'city'          => 'New York',
                'state'         => 'NY',
                'zip'           => '10001',
                'country'       => 'US',
                'is_default'    => true,
            ],
            [
                'user_id'       => $seller->id,
                'address_type'  => 'shipping',
                'first_name'    => 'John',
                'last_name'     => 'Seller',
                'phone'         => '+1 (555) 100-2000',
                'address_line1' => '123 Tech Street',
                'city'          => 'San Francisco',
                'state'         => 'CA',
                'zip'           => '94102',
                'country'       => 'US',
                'is_default'    => true,
            ],
            [
                'user_id'       => $supplier->id,
                'address_type'  => 'shipping',
                'first_name'    => 'Jane',
                'last_name'     => 'Supplier',
                'phone'         => '+1 (555) 300-4000',
                'address_line1' => '456 Industry Blvd',
                'city'          => 'Los Angeles',
                'state'         => 'CA',
                'zip'           => '90001',
                'country'       => 'US',
                'is_default'    => true,
            ],
        ];

        foreach ($addresses as $addr) {
            Address::firstOrCreate(
                ['user_id' => $addr['user_id'], 'address_type' => $addr['address_type'], 'address_line1' => $addr['address_line1']],
                $addr
            );
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('  admin@oceanovia.com    / Password@123  (Admin)');
        $this->command->info('  seller@oceanovia.com   / Password@123  (Seller)');
        $this->command->info('  supplier@oceanovia.com / Password@123  (Supplier)');
        $this->command->info('  customer@oceanovia.com / Password@123  (Customer)');
    }
}


