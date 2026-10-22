<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProcurementOrder;
use App\Models\ProcurementOrderItem;
use App\Models\SellerProfile;
use App\Models\SupplierBalance;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * R3.4 - the recovery of a supplier settlement that was lost between the
 * procurement synchronisation commit and the settlement commit.
 *
 * A crash in that window leaves a procurement order that is delivered while
 * its settlement marker is still missing, and the supplier was never
 * credited. These tests cover the repair of that state, the reconciliation
 * command that finds it, and the cases that must never be credited.
 */
class ProcurementSettlementReconciliationTest extends TestCase
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

    private function supplier(float $balance = 0.00): User
    {
        $supplier = User::factory()->create(['role_type' => 'supplier']);

        SupplierBalance::create([
            'supplier_id'     => $supplier->id,
            'balance'         => $balance,
            'pending_balance' => 0,
            'total_earned'    => $balance,
            'total_withdrawn' => 0,
            'platform_fees'   => 0,
        ]);

        return $supplier;
    }

    /**
     * A procurement order in the state a crash between the two commits
     * leaves behind: delivered, not settled, and with no supplier credit in
     * the ledger.
     */
    private function deliveredButUnsettled(
        User $supplier,
        User $seller,
        string $poStatus = 'delivered',
        string $itemStatus = 'delivered',
        float $subtotal = 120.00
    ): ProcurementOrder {
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'published',
            'price'     => $subtotal,
        ]);

        $order = Order::create([
            'user_id'  => $seller->id,
            'subtotal' => $subtotal,
            'total'    => $subtotal,
            'status'   => 'confirmed',
        ]);

        $item = OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'seller_id'    => $seller->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => $subtotal,
            'subtotal'     => $subtotal,
            'status'       => $itemStatus,
        ]);

        $po = ProcurementOrder::create([
            'order_id'    => $order->id,
            'supplier_id' => $supplier->id,
            'seller_id'   => $seller->id,
            'status'      => $poStatus,
            'subtotal'    => $subtotal,
        ]);

        ProcurementOrderItem::create([
            'procurement_order_id' => $po->id,
            'order_item_id'        => $item->id,
            'product_id'           => $product->id,
            'product_name'         => $product->name,
            'quantity'             => 1,
            'unit_cost'            => 10.00,
            'subtotal'             => $subtotal,
            'status'               => $itemStatus,
        ]);

        return $po;
    }

    private function creditEntries(ProcurementOrder $po): int
    {
        return (int) Transaction::where('reference_type', ProcurementOrder::class)
            ->where('reference_id', $po->id)
            ->where('type', 'credit')
            ->count();
    }

    private function supplierBalance(User $supplier): float
    {
        return (float) SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->balance;
    }

    /*
    |--------------------------------------------------------------------------
    | The repair itself
    |--------------------------------------------------------------------------
    */

    public function test_a_delivered_but_unsettled_procurement_order_is_settled_exactly_once(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->assertNull($po->fresh()->settled_at);

        app(ProcurementService::class)->syncProcurement($po->fresh());

        $this->assertNotNull($po->fresh()->settled_at);
        $this->assertSame(120.00, $this->supplierBalance($supplier));
        $this->assertSame(1, $this->creditEntries($po));

        // A second synchronisation must not credit the supplier again.
        app(ProcurementService::class)->syncProcurement($po->fresh());

        $this->assertSame(120.00, $this->supplierBalance($supplier));
        $this->assertSame(1, $this->creditEntries($po));
    }

    public function test_repeating_the_seller_delivery_action_repairs_the_settlement(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->assertNull($po->fresh()->settled_at);
        $this->assertSame(0.00, $this->supplierBalance($supplier));

        $response = $this->actingAs($seller)
            ->patch(route('seller.orders.update-status', $po->order), ['status' => 'delivered']);

        $response->assertRedirect();

        $this->assertNotNull($po->fresh()->settled_at, 'The seller action must repair the lost settlement.');
        $this->assertSame(120.00, $this->supplierBalance($supplier));
        $this->assertSame(1, $this->creditEntries($po));
    }

    public function test_a_settlement_failure_leaves_the_money_state_untouched(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        // The audit record is the last write of the settlement, so the failure
        // happens after the balance and the ledger entry were written.
        Event::listen('eloquent.creating: '.AuditLog::class, function (): void {
            throw new RuntimeException('the settlement audit failed');
        });

        $thrown = null;

        try {
            app(ProcurementService::class)->syncProcurement($po->fresh());
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $this->assertNull($po->fresh()->settled_at, 'The settlement marker must not survive a failed settlement.');
        $this->assertSame(0.00, $this->supplierBalance($supplier), 'The balance must not survive a failed settlement.');
        $this->assertSame(0, $this->creditEntries($po), 'The ledger entry must not survive a failed settlement.');
    }

    /*
    |--------------------------------------------------------------------------
    | The reconciliation command
    |--------------------------------------------------------------------------
    */

    public function test_the_dry_run_reports_a_candidate_and_changes_nothing(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->artisan('money:reconcile-procurement-settlements --dry-run')
            ->expectsOutputToContain($po->po_number)
            ->expectsOutputToContain('Discovered: 1')
            ->expectsOutputToContain('Repairable: 1')
            ->expectsOutputToContain('Reconciled: 0')
            ->assertExitCode(0);

        $this->assertNull($po->fresh()->settled_at);
        $this->assertSame('delivered', $po->fresh()->status);
        $this->assertSame(0.00, $this->supplierBalance($supplier));
        $this->assertSame(0, $this->creditEntries($po));
    }

    public function test_the_dry_run_is_the_default_and_changes_nothing(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->artisan('money:reconcile-procurement-settlements')
            ->expectsOutputToContain('Mode: dry run')
            ->assertExitCode(0);

        $this->assertNull($po->fresh()->settled_at);
        $this->assertSame(0.00, $this->supplierBalance($supplier));
        $this->assertSame(0, $this->creditEntries($po));
    }

    public function test_the_write_run_credits_the_supplier_once(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->artisan('money:reconcile-procurement-settlements --write')
            ->expectsOutputToContain($po->po_number)
            ->expectsOutputToContain('Reconciled: 1')
            ->assertExitCode(0);

        $this->assertNotNull($po->fresh()->settled_at);
        $this->assertSame(120.00, $this->supplierBalance($supplier));
        $this->assertSame(1, $this->creditEntries($po));
    }

    public function test_a_second_write_run_is_a_no_op(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        $this->artisan('money:reconcile-procurement-settlements --write')->assertExitCode(0);

        $this->artisan('money:reconcile-procurement-settlements --write')
            ->expectsOutputToContain('Discovered: 0')
            ->expectsOutputToContain('Reconciled: 0')
            ->assertExitCode(0);

        $this->assertSame(120.00, $this->supplierBalance($supplier));
        $this->assertSame(1, $this->creditEntries($po));
    }

    public function test_an_ambiguous_ledger_state_is_reported_and_never_credited_again(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        // The supplier was credited, but the settlement marker never landed.
        Transaction::create([
            'accountable_type' => SupplierBalance::class,
            'accountable_id'   => SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->id,
            'reference_type'   => ProcurementOrder::class,
            'reference_id'     => $po->id,
            'type'             => 'credit',
            'amount'           => 120.00,
            'description'      => 'Procurement settlement: '.$po->po_number,
            'status'           => 'completed',
            'method'           => 'procurement',
        ]);

        $this->artisan('money:reconcile-procurement-settlements --write')
            ->expectsOutputToContain($po->po_number)
            ->expectsOutputToContain('Ambiguous: 1')
            ->expectsOutputToContain('Reconciled: 0')
            ->assertExitCode(1);

        $this->assertNull($po->fresh()->settled_at, 'The ambiguous record must not be marked as settled.');
        $this->assertSame(0.00, $this->supplierBalance($supplier), 'The ambiguous record must not be credited again.');
        $this->assertSame(1, $this->creditEntries($po));
    }

    public function test_an_ambiguous_state_is_reported_in_the_log(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller);

        Transaction::create([
            'accountable_type' => SupplierBalance::class,
            'accountable_id'   => SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->id,
            'reference_type'   => ProcurementOrder::class,
            'reference_id'     => $po->id,
            'type'             => 'credit',
            'amount'           => 120.00,
            'description'      => 'Procurement settlement: '.$po->po_number,
            'status'           => 'completed',
            'method'           => 'procurement',
        ]);

        Log::spy();

        $this->artisan('money:reconcile-procurement-settlements --dry-run')->assertExitCode(1);

        Log::shouldHaveReceived('warning')->atLeast()->once();
        Log::shouldHaveReceived('info')->atLeast()->once();
    }

    public function test_the_command_rejects_the_dry_run_and_write_combination(): void
    {
        $this->artisan('money:reconcile-procurement-settlements --dry-run --write')
            ->expectsOutputToContain('Use either --dry-run or --write, not both.')
            ->assertExitCode(2);
    }

    public function test_ineligible_procurement_orders_are_not_settled(): void
    {
        $supplier = $this->supplier();
        $seller = $this->seller();

        $shipped = $this->deliveredButUnsettled($supplier, $seller, 'shipped', 'shipped');
        $cancelled = $this->deliveredButUnsettled($supplier, $seller, 'cancelled', 'cancelled');

        $service = app(ProcurementService::class);

        $this->assertSame(ProcurementService::SETTLEMENT_INELIGIBLE, $service->settlementState($shipped));
        $this->assertSame(ProcurementService::SETTLEMENT_INELIGIBLE, $service->settlementState($cancelled));

        $this->assertSame(ProcurementService::SETTLEMENT_INELIGIBLE, $service->reconcileSettlement($shipped));
        $this->assertSame(ProcurementService::SETTLEMENT_INELIGIBLE, $service->reconcileSettlement($cancelled));

        $this->assertNull($shipped->fresh()->settled_at);
        $this->assertNull($cancelled->fresh()->settled_at);
        $this->assertSame(0.00, $this->supplierBalance($supplier));
        $this->assertSame(0, $this->creditEntries($shipped));
        $this->assertSame(0, $this->creditEntries($cancelled));
    }

    public function test_a_cancelled_and_reversed_settlement_is_not_settled_again(): void
    {
        $supplier = $this->supplier(120.00);
        $seller = $this->seller();
        $po = $this->deliveredButUnsettled($supplier, $seller, 'cancelled', 'cancelled');

        // The settlement was made and then reversed: the marker is gone, the
        // ledger holds a credit and a refund, and the net credit is zero.
        $po->update(['settled_at' => now()]);

        Transaction::create([
            'accountable_type' => SupplierBalance::class,
            'accountable_id'   => SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->id,
            'reference_type'   => ProcurementOrder::class,
            'reference_id'     => $po->id,
            'type'             => 'credit',
            'amount'           => 120.00,
            'description'      => 'Procurement settlement: '.$po->po_number,
            'status'           => 'completed',
            'method'           => 'procurement',
        ]);

        Transaction::create([
            'accountable_type' => SupplierBalance::class,
            'accountable_id'   => SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail()->id,
            'reference_type'   => ProcurementOrder::class,
            'reference_id'     => $po->id,
            'type'             => 'refund',
            'amount'           => 120.00,
            'description'      => 'Procurement reversal: '.$po->po_number,
            'status'           => 'completed',
            'method'           => 'procurement',
        ]);

        $po->update(['settled_at' => null]);

        $service = app(ProcurementService::class);

        $this->assertSame(ProcurementService::SETTLEMENT_INELIGIBLE, $service->settlementState($po));
        $this->assertSame(ProcurementService::SETTLEMENT_INELIGIBLE, $service->reconcileSettlement($po));

        $this->assertNull($po->fresh()->settled_at);
        $this->assertSame(120.00, $this->supplierBalance($supplier));
    }

    /*
    |--------------------------------------------------------------------------
    | The scheduled detection
    |--------------------------------------------------------------------------
    */

    public function test_the_reconciliation_is_registered_in_the_scheduler(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('money:reconcile-procurement-settlements')
            ->assertExitCode(0);
    }
}
