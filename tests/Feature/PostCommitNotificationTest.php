<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Jobs\ProcessOrder;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\EmailLog;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PDOException;
use RuntimeException;
use Tests\TestCase;

/**
 * F-06.3 - post-commit notification failure isolation and idempotency.
 *
 * The checkout commits the order before the notification and the audit job
 * run. A failure in either of them must not be reported as a failed
 * checkout, must not roll the committed order back, and must not be recorded
 * as a successful delivery.
 *
 * The notification is deduplicated by the database: every log row carries a
 * deterministic idempotency key and a unique index on that column turns a
 * concurrent duplicate into a no-op. The key is scoped to the notification
 * flow, because (user_id, subject) is not a safe key for the table as a
 * whole - the admin mailer stores the sender in user_id and lets the admin
 * reuse a subject, so that pair must stay unconstrained.
 */
class PostCommitNotificationTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Fixtures
    |--------------------------------------------------------------------------
    */

    private function seller(): User
    {
        $seller = User::factory()->create(['role_type' => 'seller']);

        SellerProfile::factory()->create([
            'user_id'         => $seller->id,
            'commission_rate' => 10.00,
            'status'          => 'approved',
        ]);

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
    }

    private function productWithStock(User $seller, int $stock = 10, float $price = 25.00): Product
    {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $price,
        ]);

        Inventory::create([
            'product_id'     => $product->id,
            'stock_quantity' => $stock,
        ]);

        return $product;
    }

    private function fillCart(User $customer, Product $product, int $quantity = 1): Cart
    {
        $cart = Cart::create(['user_id' => $customer->id]);

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $product->price,
        ]);

        return $cart;
    }

    /**
     * Make every email log write fail with the given exception.
     */
    private function failEmailLogWrites(\Throwable $exception): void
    {
        Event::listen('eloquent.creating: '.EmailLog::class, function () use ($exception): void {
            throw $exception;
        });
    }

    /**
     * A unique violation of a constraint that is not the idempotency key.
     */
    private function otherUniqueViolation(): UniqueConstraintViolationException
    {
        return (new UniqueConstraintViolationException(
            'sqlite',
            'insert into "email_logs" ("recipient_email") values (?)',
            [],
            new PDOException('UNIQUE constraint failed: email_logs.recipient_email')
        ))->setIndex('email_logs_recipient_email_unique');
    }

    /**
     * Replace the queue with one that cannot accept any job.
     */
    private function breakTheQueue(): void
    {
        $connection = Mockery::mock(QueueContract::class);
        $connection->shouldReceive('push')->andThrow(new RuntimeException('queue is down'));
        $connection->shouldReceive('pushOn')->andThrow(new RuntimeException('queue is down'));
        $connection->shouldReceive('later')->andThrow(new RuntimeException('queue is down'));

        $manager = Mockery::mock(QueueManager::class);
        $manager->shouldIgnoreMissing();
        $manager->shouldReceive('connection')->andReturn($connection);

        $this->app->instance('queue', $manager);

        Queue::clearResolvedInstance('queue');
    }

    /*
    |--------------------------------------------------------------------------
    | A working notification
    |--------------------------------------------------------------------------
    */

    public function test_a_successful_checkout_queues_the_customer_and_admin_notifications(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $admin = $this->admin();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $order = Order::firstOrFail();

        $customerLog = EmailLog::where('user_id', $customer->id)->firstOrFail();
        $adminLog = EmailLog::where('user_id', $admin->id)->firstOrFail();

        $this->assertSame('order-placed:'.$order->id.':customer', $customerLog->idempotency_key);
        $this->assertSame('order-placed:'.$order->id.':admin', $adminLog->idempotency_key);
        $this->assertNotSame($customerLog->idempotency_key, $adminLog->idempotency_key);

        // The queue runs inline in the test environment, so both mails were
        // delivered and recorded as such.
        $this->assertSame('sent', $customerLog->fresh()->status);
        $this->assertSame('sent', $adminLog->fresh()->status);
        $this->assertNotNull($customerLog->fresh()->sent_at);
    }

    public function test_the_api_checkout_queues_the_customer_and_admin_notifications(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $admin = $this->admin();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated();

        $order = Order::firstOrFail();

        $this->assertSame(2, EmailLog::count());
        $this->assertSame(
            'order-placed:'.$order->id.':customer',
            EmailLog::where('user_id', $customer->id)->firstOrFail()->idempotency_key
        );
        $this->assertSame(
            'order-placed:'.$order->id.':admin',
            EmailLog::where('user_id', $admin->id)->firstOrFail()->idempotency_key
        );
    }

    /*
    |--------------------------------------------------------------------------
    | A failing notification must not fail the checkout
    |--------------------------------------------------------------------------
    */

    public function test_a_notification_failure_after_the_commit_does_not_fail_the_checkout(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $this->failEmailLogWrites(new RuntimeException('email log write failed'));

        Log::spy();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect()->assertSessionHas('success');

        // The checkout committed and is not rolled back by the failure.
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
        $this->assertSame(0, CartItem::count());

        // The failure is reported through the log, not through the response.
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_a_notification_failure_does_not_roll_back_the_audit_log(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->failEmailLogWrites(new RuntimeException('email log write failed'));

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        // The customer notification failed, the audit log is still written.
        $this->assertDatabaseHas('audit_logs', ['action' => 'financial.order.processed']);
    }

    public function test_a_failed_notification_is_not_recorded_as_a_successful_delivery(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->failEmailLogWrites(new RuntimeException('email log write failed'));

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $this->assertSame(0, EmailLog::where('status', 'sent')->count());
        $this->assertSame(0, EmailLog::count());
    }

    public function test_a_notification_that_cannot_be_queued_is_recorded_as_failed(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        // The log row can be written, but the queue cannot accept the mail.
        $this->breakTheQueue();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, Order::count());

        $log = EmailLog::firstOrFail();

        // The attempt is recorded as a failure, never as a delivery.
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('queue is down', (string) $log->error_message);
        $this->assertNull($log->sent_at);
        $this->assertSame(0, EmailLog::where('status', 'sent')->count());
    }

    public function test_an_unrelated_database_error_is_not_misclassified_as_a_duplicate(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        // A unique violation of a different constraint is a real failure, not
        // a duplicate notification.
        $this->failEmailLogWrites($this->otherUniqueViolation());

        Log::spy();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect()->assertSessionHas('success');

        Log::shouldHaveReceived('error')->atLeast()->once();
        $this->assertSame(0, EmailLog::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    */

    public function test_repeated_processing_of_the_same_notification_does_not_create_duplicate_logs(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $admin = $this->admin();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        // The event is dispatched by the controller and again by the order
        // processing job. Neither may produce a second email.
        Log::spy();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $this->assertSame(2, EmailLog::count());
        $this->assertSame(1, EmailLog::where('user_id', $customer->id)->count());
        $this->assertSame(1, EmailLog::where('user_id', $admin->id)->count());

        // A duplicate is not an error.
        Log::shouldNotHaveReceived('error');
    }

    public function test_a_duplicate_notification_is_skipped_without_creating_a_second_row(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $admin = $this->admin();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $before = EmailLog::count();

        // The row for this notification already exists, so the insert hits the
        // unique index. That is a duplicate, not a failure.
        Log::spy();

        event(new OrderPlaced($order));

        $this->assertSame($before, EmailLog::count());
        Log::shouldNotHaveReceived('error');
    }

    public function test_distinct_notifications_that_share_a_user_and_subject_are_still_allowed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.emails.send'), [
            'recipients' => 'one@example.com',
            'subject'    => 'Newsletter',
            'message'    => 'First campaign',
            'body_type'  => 'text',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.emails.send'), [
            'recipients' => 'one@example.com',
            'subject'    => 'Newsletter',
            'message'    => 'Second campaign',
            'body_type'  => 'text',
        ])->assertSessionHasNoErrors();

        // The same sender and the same subject are legitimate: the admin
        // mailer is not deduplicated and must keep working.
        $this->assertSame(2, EmailLog::where('user_id', $admin->id)->where('subject', 'Newsletter')->count());
        $this->assertSame(2, EmailLog::whereNull('idempotency_key')->count());
    }

    /*
    |--------------------------------------------------------------------------
    | The dispatch itself must not fail the checkout
    |--------------------------------------------------------------------------
    */

    public function test_a_notification_failure_does_not_retry_the_checkout_or_duplicate_the_dispatch(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller, 10);

        $this->fillCart($customer, $product, 2);

        $this->failEmailLogWrites(new RuntimeException('email log write failed'));

        Queue::fake();

        $dispatches = 0;
        Event::listen(OrderPlaced::class, function () use (&$dispatches): void {
            $dispatches++;
        });

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect();

        // One order, one event, one job - the failure did not retry anything.
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(1, $dispatches);
        Queue::assertPushed(ProcessOrder::class, 1);
        $this->assertSame(8, Inventory::where('product_id', $product->id)->firstOrFail()->stock_quantity);
    }

    public function test_a_failing_job_dispatch_does_not_fail_the_checkout(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        // The queue cannot accept the order processing job.
        $this->breakTheQueue();

        Log::spy();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect()->assertSessionHas('success');

        // The order is committed and the checkout is not reported as failed.
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());

        // The dispatch failure is reported through the log.
        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    /*
    |--------------------------------------------------------------------------
    | Response contracts
    |--------------------------------------------------------------------------
    */

    public function test_the_web_checkout_response_contract_is_preserved_when_the_notification_fails(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->failEmailLogWrites(new RuntimeException('email log write failed'));

        $this->actingAs($customer)->post(route('checkout.store'), [
            'shipping_address_id' => $address->id,
            'payment_method'      => 'cod',
            'billing_same'        => '1',
        ])->assertRedirect(route('orders.show', Order::firstOrFail()))
            ->assertSessionHas('success');
    }

    public function test_the_api_checkout_response_contract_is_preserved_when_the_notification_fails(): void
    {
        $seller = $this->seller();
        $customer = $this->customer();
        $address = $this->addressFor($customer);
        $product = $this->productWithStock($seller);

        $this->fillCart($customer, $product, 1);

        $this->failEmailLogWrites(new RuntimeException('email log write failed'));

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/checkout', [
            'shipping_address_id' => $address->id,
            'billing_address_id'  => $address->id,
            'payment_method'      => 'cod',
        ])->assertCreated()
            ->assertJsonPath('message', 'Order placed successfully. Payment will be collected on delivery.');
    }
}
