<?php

namespace Tests\Feature;

use App\Exceptions\PayoutStateException;
use App\Models\AuditLog;
use App\Models\SellerBalance;
use App\Models\SellerPayout;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        return User::factory()->create(['role_type' => 'seller']);
    }

    public function test_credit_increases_balance_and_records_transaction(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();

        $service->credit($seller->id, 120.50);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(120.50, (float) $balance->balance);
        $this->assertEquals(120.50, (float) $balance->total_earned);
        $this->assertDatabaseHas('transactions', [
            'accountable_type' => SellerBalance::class,
            'type'             => 'credit',
            'method'           => 'sale',
        ]);
    }

    public function test_request_payout_moves_balance_to_withdrawn(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 40.00, 'bank_transfer');

        $this->assertSame('pending', $payout->status);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(60.00, (float) $balance->balance);
        $this->assertEquals(40.00, (float) $balance->total_withdrawn);
    }

    public function test_request_payout_rejects_amount_over_balance_without_mutating(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 50.00);

        try {
            $service->requestPayout($seller->id, 80.00, 'bank_transfer');
            $this->fail('Expected InvalidArgumentException for insufficient balance.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Insufficient balance.', $e->getMessage());
        }

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(50.00, (float) $balance->balance);
        $this->assertEquals(0.00, (float) $balance->total_withdrawn);
        $this->assertSame(0, SellerPayout::count());
    }

    public function test_rejecting_payout_restores_balance(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');
        $service->reject($payout, 'wrong account');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(100.00, (float) $balance->balance);
        $this->assertEquals(0.00, (float) $balance->total_withdrawn);
        $this->assertSame('rejected', $payout->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | The service guards the transitions
    |--------------------------------------------------------------------------
    */

    public function test_the_service_rejects_a_payout_of_zero_or_less(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        foreach ([0.0, -30.00] as $amount) {
            try {
                $service->requestPayout($seller->id, $amount, 'bank_transfer');
                $this->fail('Expected an InvalidArgumentException for the amount '.$amount.'.');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('The amount must be greater than zero.', $e->getMessage());
            }
        }

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(100.00, (float) $balance->balance, 'A refused payout must not touch the balance.');
        $this->assertEquals(0.00, (float) $balance->total_withdrawn);
        $this->assertSame(0, SellerPayout::count());
        $this->assertSame(0, Transaction::where('type', 'payout')->count());
    }

    public function test_a_payout_cannot_be_approved_from_an_invalid_state(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');
        $service->approve($payout);

        foreach (['approved', 'rejected', 'completed'] as $state) {
            $payout->update(['status' => $state]);

            try {
                $service->approve($payout->fresh());
                $this->fail('Expected a PayoutStateException for the status '.$state.'.');
            } catch (PayoutStateException $e) {
                $this->assertStringContainsString($state, $e->getMessage());
            }

            $this->assertSame($state, $payout->fresh()->status, 'A refused approval must not change the payout.');
        }

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(70.00, (float) $balance->balance, 'A refused approval must not touch the balance.');
        $this->assertEquals(30.00, (float) $balance->total_withdrawn);
        $this->assertSame(0, AuditLog::where('action', 'financial.payout_approved')->count());
    }

    public function test_a_payout_cannot_be_completed_while_it_is_pending(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');

        try {
            $service->complete($payout);
            $this->fail('Expected a PayoutStateException for a pending payout.');
        } catch (PayoutStateException $e) {
            $this->assertStringContainsString('pending', $e->getMessage());
        }

        $payout = $payout->fresh();
        $this->assertSame('pending', $payout->status, 'A refused completion must not change the payout.');
        $this->assertNull($payout->completed_at);
        $this->assertNull($payout->paid_at);

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(70.00, (float) $balance->balance, 'A refused completion must not touch the balance.');
    }

    public function test_a_payout_cannot_be_rejected_after_it_was_completed(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');
        $service->approve($payout);
        $service->complete($payout->fresh());

        try {
            $service->reject($payout->fresh(), 'too late');
            $this->fail('Expected a PayoutStateException for a completed payout.');
        } catch (PayoutStateException $e) {
            $this->assertStringContainsString('completed', $e->getMessage());
        }

        $this->assertSame('completed', $payout->fresh()->status, 'A refused rejection must not change the payout.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(
            70.00,
            (float) $balance->balance,
            'A completed payout must not give the funds back a second time.'
        );
        $this->assertEquals(30.00, (float) $balance->total_withdrawn);
    }

    public function test_a_repeated_rejection_does_not_restore_the_funds_twice(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');
        $service->reject($payout, 'wrong account');

        try {
            $service->reject($payout->fresh(), 'wrong account');
            $this->fail('Expected a PayoutStateException for a rejected payout.');
        } catch (PayoutStateException $e) {
            $this->assertStringContainsString('rejected', $e->getMessage());
        }

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(
            100.00,
            (float) $balance->balance,
            'The funds must be restored once, not twice.'
        );
        $this->assertEquals(0.00, (float) $balance->total_withdrawn);
        $this->assertSame(1, AuditLog::where('action', 'financial.payout_rejected')->count());
    }

    public function test_a_repeated_completion_does_not_repeat_the_effect(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');
        $service->approve($payout);
        $service->complete($payout->fresh());

        $completedAt = $payout->fresh()->completed_at;

        $service->complete($payout->fresh());

        $payout = $payout->fresh();
        $this->assertSame('completed', $payout->status);
        $this->assertEquals(
            $completedAt,
            $payout->completed_at,
            'A repeated completion must not move the recorded moment.'
        );

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(
            70.00,
            (float) $balance->balance,
            'A repeated completion must not take the funds a second time.'
        );
        $this->assertEquals(30.00, (float) $balance->total_withdrawn);
        $this->assertSame(1, Transaction::where('type', 'payout')->count());
    }

    public function test_the_valid_lifecycle_keeps_the_balance_the_payout_and_the_ledger_right(): void
    {
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');

        $this->assertSame('pending', $payout->status);
        $this->assertSame(1, Transaction::where('type', 'payout')->count(), 'The reservation writes one ledger row.');

        $service->approve($payout);
        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertNotNull($payout->fresh()->approved_at);

        $service->complete($payout->fresh());

        $payout = $payout->fresh();
        $this->assertSame('completed', $payout->status);
        $this->assertNotNull($payout->completed_at);
        $this->assertNotNull($payout->paid_at, 'A completed payout records when it was paid.');

        $balance = SellerBalance::where('seller_id', $seller->id)->firstOrFail();
        $this->assertEquals(70.00, (float) $balance->balance, 'The amount is reserved once, at the request.');
        $this->assertEquals(30.00, (float) $balance->total_withdrawn);
        $this->assertEquals(100.00, (float) $balance->total_earned);
        $this->assertSame(1, Transaction::where('type', 'payout')->count());
    }

    public function test_the_admin_route_refuses_a_payout_that_is_not_pending(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin', 'email_verified_at' => now()]);
        $seller = $this->seller();
        $service = new PayoutService();
        $service->credit($seller->id, 100.00);

        $payout = $service->requestPayout($seller->id, 30.00, 'bank_transfer');
        $service->approve($payout);

        $response = $this->actingAs($admin)->post(route('admin.payouts.approve', $payout));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('approved', $payout->fresh()->status);
    }
}
