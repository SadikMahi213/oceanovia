<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The order number of a new order.
 *
 * The number is unique in the database, and two orders that are created at the
 * same moment - in the same process or in two different ones - must never
 * carry the same number. uniqid() could not guarantee that: it is derived from
 * the clock alone, so two processes that created an order in the same
 * microsecond produced the same number and the second insert failed with a
 * duplicate key error (MySQL 1062) instead of an order.
 */
class OrderNumberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The shape the column and the interface allow: the ORD- prefix the
     * interface shows, followed by the canonical 26 character base32 form of a
     * ULID. The whole value has to fit the 30 character column.
     */
    private const FORMAT = '/^ORD-[0-9A-HJKMNP-TV-Z]{26}$/';

    public function test_a_new_order_receives_an_order_number(): void
    {
        $order = Order::create($this->orderAttributes());

        $this->assertNotEmpty($order->order_number, 'A new order must carry an order number.');
        $this->assertMatchesRegularExpression(
            self::FORMAT,
            $order->order_number,
            'The order number must keep the ORD- prefix and the canonical identifier: '.$order->order_number
        );
    }

    public function test_the_order_number_fits_the_order_number_column(): void
    {
        $order = Order::create($this->orderAttributes());

        $this->assertLessThanOrEqual(
            30,
            strlen((string) $order->order_number),
            'The order number must fit the 30 character column of the orders table.'
        );

        // The value the column really holds, not only the value on the model.
        $stored = Order::query()->whereKey($order->id)->value('order_number');

        $this->assertSame($order->order_number, $stored, 'The order number must be stored unchanged.');
    }

    public function test_two_order_numbers_for_the_same_instant_are_different(): void
    {
        $instant = new \DateTimeImmutable('2026-01-01 12:00:00.000000');

        $first = Order::generateOrderNumber($instant);
        $second = Order::generateOrderNumber($instant);

        $this->assertNotSame(
            $first,
            $second,
            'Two order numbers for the same instant must differ: a number that is derived from the clock '
            .'alone collides when two processes create an order in the same microsecond.'
        );

        $this->assertMatchesRegularExpression(self::FORMAT, $first);
        $this->assertMatchesRegularExpression(self::FORMAT, $second);
    }

    public function test_independently_created_orders_receive_distinct_order_numbers(): void
    {
        $numbers = [];

        for ($i = 0; $i < 50; $i++) {
            $order = Order::create($this->orderAttributes());

            $this->assertMatchesRegularExpression(
                self::FORMAT,
                $order->order_number,
                'Every order number must keep the canonical shape: '.$order->order_number
            );

            $numbers[] = $order->order_number;
        }

        $this->assertCount(
            50,
            array_unique($numbers),
            'Fifty orders created in a row must carry fifty different order numbers.'
        );

        $this->assertSame(50, Order::query()->count(), 'Every order must be stored.');
    }

    public function test_the_database_still_rejects_a_duplicate_order_number(): void
    {
        $order = Order::create($this->orderAttributes());

        $this->expectException(QueryException::class);

        Order::query()->create($this->orderAttributes(['order_number' => $order->order_number]));
    }

    public function test_an_explicitly_provided_order_number_is_kept(): void
    {
        $order = Order::create($this->orderAttributes(['order_number' => 'ORD-EXPLICIT000000000000000000']));

        $this->assertSame(
            'ORD-EXPLICIT000000000000000000',
            $order->order_number,
            'An order number that was given explicitly must be kept.'
        );
    }

    public function test_the_checkout_stores_the_generated_order_number(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role_type' => 'seller']);
        $category = Category::factory()->create(['status' => true]);

        $product = Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'status'      => 'published',
            'price'       => 39.00,
        ]);

        Inventory::create(['product_id' => $product->id, 'stock_quantity' => 3]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => $product->price,
        ]);

        $address = Address::create([
            'user_id'       => $user->id,
            'address_type'  => 'shipping',
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'phone'         => '1234567890',
            'address_line1' => '123 Main St',
            'city'          => 'Los Angeles',
            'state'         => 'CA',
            'zip'           => '90001',
            'country'       => 'US',
        ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ]);

        $response->assertRedirect();

        $order = Order::query()->sole();

        $this->assertMatchesRegularExpression(
            self::FORMAT,
            (string) $order->order_number,
            'The checkout must store the generated order number: '.$order->order_number
        );

        $this->assertSame(
            2,
            (int) Inventory::query()->where('product_id', $product->id)->value('stock_quantity'),
            'The checkout must still consume the stock once.'
        );

        $this->assertSame(
            0,
            CartItem::query()->where('cart_id', $cart->id)->count(),
            'The checkout must still empty the cart.'
        );
    }

    /**
     * The attributes of an order the test creates directly.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderAttributes(array $overrides = []): array
    {
        $user = User::factory()->create();

        return array_merge([
            'user_id'        => $user->id,
            'subtotal'       => 25.00,
            'shipping_cost'  => 0,
            'tax'            => 0,
            'discount'       => 0,
            'total'          => 25.00,
            'status'         => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ], $overrides);
    }
}
