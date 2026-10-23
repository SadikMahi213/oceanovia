<?php

namespace App\Services;

use App\Jobs\SendAdminEmail;
use App\Models\CodCollection;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\AdminRecipient;
use App\Support\TransactionRetry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CodCollectionService
{
    /**
     * Resolve who physically collects cash for an order item from the order
     * snapshot (UD-1): retail lines -> seller, sourced lines -> supplier.
     *
     * @return array{id: ?int, role: string}
     */
    public function resolveCollector(OrderItem $item): array
    {
        if ($item->supplier_product_id) {
            return [
                'id'   => $item->supplier_id,
                'role' => 'supplier',
            ];
        }

        return [
            'id'   => $item->seller_id,
            'role' => 'seller',
        ];
    }

    /**
     * Expected cash per line: line subtotal plus a proportional share of
     * order-level shipping, tax and discount (UD-7).
     */
    public function expectedAmountForItem(Order $order, OrderItem $item): float
    {
        $subtotal = (float) $item->subtotal;
        $orderSubtotal = (float) $order->subtotal;

        if ($orderSubtotal <= 0) {
            return round($subtotal, 2);
        }

        $adjustments = (float) $order->shipping_cost + (float) $order->tax - (float) $order->discount;

        return round($subtotal + ($adjustments * $subtotal / $orderSubtotal), 2);
    }

    public function createForOrder(Order $order): void
    {
        if ($order->payment_method !== 'cod') {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            // Stock-held items are not billable yet: no cash to collect until
            // the seller/supplier confirms the shortfall is sourced.
            if ($item->status === OrderItem::STATUS_AWAITING_STOCK) {
                continue;
            }

            $collector = $this->resolveCollector($item);

            CodCollection::firstOrCreate(
                ['order_item_id' => $item->id],
                [
                    'order_id'         => $order->id,
                    'collector_id'     => $collector['id'],
                    'collector_role'   => $collector['role'],
                    'expected_amount'  => $this->expectedAmountForItem($order, $item),
                    'collected_amount' => 0,
                    'shortfall_amount' => 0,
                    'overpaid_amount'  => 0,
                    'status'           => 'pending',
                ]
            );
        }
    }

    /**
     * Record a verified collection. Admin-only (UD-2). Credits the seller's
     * available balance immediately for the actual amount collected (UD-3),
     * flags shortfalls for review (UD-4) and never over-credits.
     *
     * The credit and the collection record are one financial operation, so
     * they share this transaction. It is the outermost transaction of the
     * workflow, which is why it can ask for retries of its own.
     */
    public function recordCollection(
        CodCollection $collection,
        float $collectedAmount,
        int $collectedBy,
        ?string $notes = null,
    ): CodCollection {
        return DB::transaction(function () use ($collection, $collectedAmount, $collectedBy, $notes) {
            $locked = CodCollection::whereKey($collection->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                return $locked;
            }

            $order = $locked->order;

            if ($order === null || in_array($order->status, ['cancelled', 'refunded'], true)) {
                throw ValidationException::withMessages([
                    'collected_amount' => 'Cash cannot be recorded for a cancelled or refunded order.',
                ]);
            }

            if ($locked->orderItem?->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'collected_amount' => 'This order item was cancelled and cannot be collected.',
                ]);
            }

            $expected = (float) $locked->expected_amount;
            $actual = max(0.0, round($collectedAmount, 2));

            $shortfall = round(max(0.0, $expected - $actual), 2);
            $overpaid = round(max(0.0, $actual - $expected), 2);

            $locked->update([
                'collected_amount' => $actual,
                'shortfall_amount' => $shortfall,
                'overpaid_amount'  => $overpaid,
                'status'           => 'collected',
                'requires_review'  => $shortfall > 0,
                'collected_by'     => $collectedBy,
                'collected_at'     => now(),
                'notes'            => $notes,
            ]);

            $credited = $this->creditSellerForCollection($locked, $actual, $expected);

            app(AuditService::class)->logFinancial('cod_collection_recorded', $locked, [
                'order_id'         => $locked->order_id,
                'order_item_id'    => $locked->order_item_id,
                'expected_amount'  => $expected,
                'collected_amount' => $actual,
                'shortfall_amount' => $shortfall,
                'overpaid_amount'  => $overpaid,
                'seller_credit'    => $credited,
                'requires_review'  => $shortfall > 0,
            ]);

            if ($shortfall > 0) {
                $collectionId = $locked->id;

                DB::afterCommit(fn () => $this->notifyAdminsOfShortfall($collectionId));
            }

            return $locked->fresh();
        }, TransactionRetry::ATTEMPTS);
    }

    /**
     * Alert admins that a recorded COD collection was short and needs review
     * (UD-4). Uses the existing EmailLog + SendAdminEmail pipeline and is
     * idempotent per admin and collection.
     */
    private function notifyAdminsOfShortfall(int $collectionId): void
    {
        $collection = CodCollection::with('order')->find($collectionId);

        if ($collection === null) {
            return;
        }

        $admin = AdminRecipient::resolve();

        if ($admin === null || ! $admin['email']) {
            return;
        }

        $subject = 'Oceanovia — COD collection #'.$collection->id.' needs review';

        $body = "A cash-on-delivery collection was recorded short and needs review."."\n\n"
            ."Collection ID: {$collection->id}"."\n"
            ."Order: ".($collection->order?->order_number ?? '—')."\n"
            .'Expected: $'.number_format((float) $collection->expected_amount, 2)."\n"
            .'Collected: $'.number_format((float) $collection->collected_amount, 2)."\n"
            .'Shortfall: $'.number_format((float) $collection->shortfall_amount, 2)."\n\n"
            .'Review it in the admin COD collections queue.';

        $alreadyQueued = EmailLog::query()
            ->where('user_id', $admin['user_id'])
            ->where('subject', $subject)
            ->exists();

        if ($alreadyQueued) {
            return;
        }

        $log = EmailLog::create([
            'user_id'         => $admin['user_id'],
            'recipient_email' => $admin['email'],
            'recipient_name'  => $admin['name'],
            'subject'         => $subject,
            'message'         => $body,
            'body_type'       => 'text',
            'status'          => 'requested',
        ]);

        SendAdminEmail::dispatch($log);
    }

    private function creditSellerForCollection(CodCollection $collection, float $actual, float $expected): float
    {
        $item = $collection->orderItem;

        if ($item === null || $item->seller_id === null || $expected <= 0 || $actual <= 0) {
            return 0.0;
        }

        $commissionService = app(CommissionService::class);
        $commission = (float) $commissionService->getCommissionForItem($item)->amount;
        $sourcingCost = $item->supplier_product_id
            ? (float) ($item->unit_cost ?? 0) * $item->quantity
            : 0.0;

        $net = round((float) $item->subtotal - $commission - $sourcingCost, 2);

        if ($net <= 0) {
            return 0.0;
        }

        $ratio = min(1.0, $actual / $expected);
        $credit = round($net * $ratio, 2);

        if ($credit <= 0) {
            return 0.0;
        }

        app(PayoutService::class)->credit($item->seller_id, $credit);

        $collectionId = $collection->id;

        DB::afterCommit(fn () => $this->notifySellerOfCredit($collectionId, $credit));

        return $credit;
    }

    /**
     * Tell the seller their balance was credited for a verified COD collection.
     * Idempotent per seller and collection through the EmailLog pipeline.
     */
    private function notifySellerOfCredit(int $collectionId, float $credit): void
    {
        $collection = CodCollection::with(['order', 'orderItem.seller'])->find($collectionId);

        if ($collection === null || $credit <= 0) {
            return;
        }

        $seller = $collection->orderItem?->seller;

        if ($seller === null || ! $seller->email) {
            return;
        }

        $subject = 'Oceanovia — COD collection #'.$collection->id.' credited to your balance';

        $alreadyQueued = EmailLog::query()
            ->where('user_id', $seller->id)
            ->where('subject', $subject)
            ->exists();

        if ($alreadyQueued) {
            return;
        }

        $body = "Hello {$seller->name},"."\n\n"
            .'A cash-on-delivery collection was verified and your balance has been credited.'."\n\n"
            .'Order: '.($collection->order?->order_number ?? '—')."\n"
            .'Item: '.($collection->orderItem?->product_name ?? '—')."\n"
            .'Amount credited: $'.number_format($credit, 2)."\n\n"
            ."You can view your balance and payouts from your seller dashboard.\n\n"
            ."Thank you for choosing\nOceanovia.com.";

        $log = EmailLog::create([
            'user_id'         => $seller->id,
            'recipient_email' => $seller->email,
            'recipient_name'  => $seller->name,
            'subject'         => $subject,
            'message'         => $body,
            'body_type'       => 'text',
            'status'          => 'requested',
        ]);

        SendAdminEmail::dispatch($log);
    }
}
