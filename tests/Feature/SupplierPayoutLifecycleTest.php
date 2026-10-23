<?php

namespace Tests\Feature;

use App\Exceptions\PayoutStateException;
use App\Models\SupplierBalance;
use App\Models\SupplierPayout;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SupplierPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPayoutLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function supplier(float $balance = 500.00): User
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

    private function request(User $supplier, float $amount = 100.00): SupplierPayout
    {
        return app(SupplierPayoutService::class)->request($supplier->id, [
            'amount'           => $amount,
            'payment_method'   => 'bank',
            'account_details'  => ['iban' => 'DE00'],
        ]);
    }

    public function test_a_request_reserves_the_amount_and_never_leaves_it_stranded(): void
    {
        $supplier = $this->supplier(500.00);

        $payout = $this->request($supplier, 100.00);

        $balance = SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame(400.00, (float) $balance->balance);
        $this->assertSame(100.00, (float) $balance->pending_balance);
        $this->assertSame('pending', $payout->status);
        $this->assertSame(
            1,
            Transaction::where('reference_type', SupplierPayout::class)
                ->where('reference_id', $payout->id)
                ->where('type', 'payout')
                ->where('status', 'pending')
                ->count()
        );
    }

    public function test_a_request_above_the_balance_is_refused(): void
    {
        $supplier = $this->supplier(50.00);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->request($supplier, 100.00);

        $this->assertSame(0, SupplierPayout::count());
    }

    public function test_approve_then_complete_moves_the_reserved_amount_to_withdrawn(): void
    {
        $supplier = $this->supplier(500.00);
        $admin = User::factory()->create(['role_type' => 'admin']);
        $payout = $this->request($supplier, 100.00);

        $service = app(SupplierPayoutService::class);
        $service->approve($payout, $admin->id);
        $this->assertSame('processing', $payout->fresh()->status);

        $service->complete($payout->fresh(), $admin->id);

        $balance = SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame('completed', $payout->fresh()->status);
        $this->assertSame(400.00, (float) $balance->balance);
        $this->assertSame(0.00, (float) $balance->pending_balance);
        $this->assertSame(100.00, (float) $balance->total_withdrawn);
        $this->assertSame(
            1,
            Transaction::where('reference_id', $payout->id)->where('status', 'completed')->count()
        );
    }

    public function test_rejecting_returns_the_reserved_amount_to_the_balance(): void
    {
        $supplier = $this->supplier(500.00);
        $admin = User::factory()->create(['role_type' => 'admin']);
        $payout = $this->request($supplier, 100.00);

        app(SupplierPayoutService::class)->reject($payout, $admin->id, 'Invalid account');

        $balance = SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame('rejected', $payout->fresh()->status);
        $this->assertSame('Invalid account', $payout->fresh()->rejection_reason);
        $this->assertSame(500.00, (float) $balance->balance);
        $this->assertSame(0.00, (float) $balance->pending_balance);
    }

    public function test_repeated_completion_does_not_double_count(): void
    {
        $supplier = $this->supplier(500.00);
        $admin = User::factory()->create(['role_type' => 'admin']);
        $payout = $this->request($supplier, 100.00);

        $service = app(SupplierPayoutService::class);
        $service->approve($payout, $admin->id);
        $service->complete($payout->fresh(), $admin->id);
        $service->complete($payout->fresh(), $admin->id);

        $balance = SupplierBalance::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame(100.00, (float) $balance->total_withdrawn);
        $this->assertSame(0.00, (float) $balance->pending_balance);
    }

    public function test_a_completed_payout_cannot_be_rejected(): void
    {
        $supplier = $this->supplier(500.00);
        $admin = User::factory()->create(['role_type' => 'admin']);
        $payout = $this->request($supplier, 100.00);

        $service = app(SupplierPayoutService::class);
        $service->approve($payout, $admin->id);
        $service->complete($payout->fresh(), $admin->id);

        $this->expectException(PayoutStateException::class);

        $service->reject($payout->fresh(), $admin->id, 'Too late');
    }

    public function test_the_admin_can_approve_and_complete_through_the_routes(): void
    {
        $supplier = $this->supplier(500.00);
        $admin = User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);
        $payout = $this->request($supplier, 100.00);

        $this->actingAs($admin)
            ->post(route('admin.supplier-payouts.approve', $payout))
            ->assertRedirect();
        $this->assertSame('processing', $payout->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.supplier-payouts.complete', $payout))
            ->assertRedirect();
        $this->assertSame('completed', $payout->fresh()->status);
    }
}
