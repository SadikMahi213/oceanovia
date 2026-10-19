<?php

namespace Tests\Feature;

use App\Models\SellerBalance;
use App\Models\SellerPayout;
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
}
