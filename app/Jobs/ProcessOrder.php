<?php

namespace App\Jobs;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Services\AuditService;
use App\Services\CommissionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function handle(): void
    {
        Log::info('Processing order', ['order_id' => $this->order->id]);

        try {
            DB::transaction(function () {
                $order = $this->order->fresh();

                // Create commission records (if not already created)
                app(CommissionService::class)->createCommissions($order);

                // Audit log
                app(AuditService::class)->logFinancial('order.processed', $order, [
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'payment_method' => $order->payment_method,
                ]);

                // Dispatch event for email notification
                event(new OrderPlaced($order));
            });
        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
