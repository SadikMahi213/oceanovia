<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\ProcurementOrder;
use App\Models\ProcurementOrderItem;
use App\Models\SupplierProductStock;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Manages reservation/commit/release of supplier source stock.
 *
 * A supplier product carries a single source-of-truth stock row:
 *   stock_quantity    = physical units on hand
 *   reserved_quantity = units committed to open procurements
 *   sold_quantity     = cumulative units ever committed (shipped)
 * Units available for sale = stock_quantity - reserved_quantity.
 */
class SourceStockService
{
    /**
     * Reserve units for cart items at checkout. Row-locked against oversell.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\CartItem>  $cartItems
     */
    public function reserveForCart(Collection $cartItems): void
    {
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product || ! $product->supplier_product_id) {
                continue;
            }

            $stock = SupplierProductStock::where('supplier_product_id', $product->supplier_product_id)
                ->lockForUpdate()
                ->first();

            $available = $stock ? $stock->stock_quantity - $stock->reserved_quantity : 0;

            if ($available < $item->quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Insufficient stock for \"{$product->name}\". Please adjust your cart.",
                ]);
            }

            $stock->increment('reserved_quantity', $item->quantity);
        }
    }

    /**
     * Release reservations for a cancelled order (before the source stock
     * was committed by shipping). Items whose procurement already shipped
     * are skipped - their stock was committed, not reserved.
     */
    public function releaseForOrder(int $orderId): void
    {
        $items = OrderItem::where('order_id', $orderId)
            ->whereNotNull('supplier_product_id')
            ->get();

        foreach ($items as $item) {
            $committed = ProcurementOrderItem::where('order_item_id', $item->id)
                ->whereHas('procurementOrder', fn ($q) => $q->whereIn('status', ['shipped', 'delivered']))
                ->exists();

            if ($committed) {
                continue;
            }

            $this->releaseReserved($item->supplier_product_id, $item->quantity);
        }
    }

    /**
     * Commit reserved units for a procurement that has shipped: reserved
     * units become sold and are removed from physical on-hand stock.
     */
    public function commitForProcurement(ProcurementOrder $procurementOrder): void
    {
        foreach ($procurementOrder->items as $item) {
            if (! $item->supplier_product_id) {
                continue;
            }

            $stock = SupplierProductStock::where('supplier_product_id', $item->supplier_product_id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                continue;
            }

            $stock->update([
                'reserved_quantity' => max(0, $stock->reserved_quantity - $item->quantity),
                'stock_quantity'    => max(0, $stock->stock_quantity - $item->quantity),
                'sold_quantity'     => $stock->sold_quantity + $item->quantity,
            ]);
        }
    }

    /**
     * Release reserved units back to available for a cancelled/returned
     * procurement that never shipped.
     */
    public function releaseForProcurement(ProcurementOrder $procurementOrder): void
    {
        foreach ($procurementOrder->items as $item) {
            if (! $item->supplier_product_id) {
                continue;
            }

            $this->releaseReserved($item->supplier_product_id, $item->quantity);
        }
    }

    private function releaseReserved(int $supplierProductId, int $quantity): void
    {
        $stock = SupplierProductStock::where('supplier_product_id', $supplierProductId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->decrement('reserved_quantity', min($quantity, $stock->reserved_quantity));
        }
    }
}