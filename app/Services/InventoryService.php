<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Inventory;
use App\Support\StockPartition;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private readonly SourceStockService $sourceStock) {}

    /**
     * Commit the cart's stock: reserve supplier source stock and decrement
     * the local inventory row for every item.
     *
     * Must be called from inside the order-creation transaction so that an
     * insufficient-stock failure rolls the whole order back (no order, no
     * order items, no commissions, no stock movement, no cart deletion).
     *
     * Stock semantics (identical for every checkout entry point):
     *  - Sourced items (the product is linked to a supplier product) are
     *    reserved against the supplier's source stock, not the local row.
     *  - Products without an inventory row are not stock-managed and are
     *    therefore not checked or decremented.
     *  - Every other product is checked and decremented against its own
     *    inventory row.
     */
    public function reserveAndDecrementForCart(Cart $cart): void
    {
        $partition = $this->partitionForCart($cart);

        if ($partition->hasHeld()) {
            $firstHeld = null;

            foreach ($cart->items as $item) {
                if (($partition->held[$item->id] ?? 0) > 0) {
                    $firstHeld = $item->product?->name;
                    break;
                }
            }

            throw ValidationException::withMessages([
                'cart' => 'Insufficient stock for "'.($firstHeld ?? 'an item').'". Please adjust your cart.',
            ]);
        }
    }

    /**
     * Partition the cart's requested quantities against the stock that is
     * actually available. Reserve only what can be fulfilled and record the
     * shortfall as held. The locking protocol is identical to the strict
     * reserve path, so the deterministic lock ordering is preserved.
     *
     * @return \App\Support\StockPartition
     */
    public function partitionForCart(Cart $cart): StockPartition
    {
        // Source stock first (supplier products), row-locked deterministically.
        $sourceResult = $this->sourceStock->partitionForCart($cart->items);

        // Collect the local inventory rows this operation touches, then lock
        // them in a deterministic ascending order before making any decision.
        $inventoryIds = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product || $product->supplier_product_id) {
                continue;
            }

            $inventory = $product->inventory;

            if ($inventory) {
                $inventoryIds[$inventory->id] = true;
            }
        }

        ksort($inventoryIds);

        $locked = [];
        foreach (array_keys($inventoryIds) as $inventoryId) {
            $locked[$inventoryId] = Inventory::whereKey($inventoryId)->lockForUpdate()->first();
        }

        $confirmed = $sourceResult->confirmed;
        $held = $sourceResult->held;

        // Re-check every quantity against the row we actually locked, and
        // reserve only the available portion of each local item.
        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product || $product->supplier_product_id) {
                continue;
            }

            $inventory = $product->inventory;

            if (! $inventory) {
                // Not stock-managed: treat the whole requested quantity as
                // confirmed, matching the original semantics.
                $confirmed[$item->id] = $item->quantity;
                continue;
            }

            $row = $locked[$inventory->id] ?? null;

            if (! $row) {
                $confirmed[$item->id] = $item->quantity;
                continue;
            }

            $available = (int) $row->stock_quantity;
            $reservedQty = min($item->quantity, $available);

            if ($reservedQty > 0) {
                $row->decrement('stock_quantity', $reservedQty);
            }

            if ($reservedQty >= $item->quantity) {
                $confirmed[$item->id] = $item->quantity;
            } else {
                if ($reservedQty > 0) {
                    $confirmed[$item->id] = $reservedQty;
                }
                $shortfall = $item->quantity - $reservedQty;
                if ($shortfall > 0) {
                    $held[$item->id] = $shortfall;
                }
            }
        }

        // Items not present in either map (no inventory row handled above
        // already) default to confirmed for sourced items handled by source
        // stock; for sourced items the source result already populated the map.
        foreach ($cart->items as $item) {
            if (! isset($confirmed[$item->id]) && ! isset($held[$item->id])) {
                $product = $item->product;

                if ($product && $product->supplier_product_id) {
                    // Already handled by the source result; leave as-is.
                    continue;
                }

                $confirmed[$item->id] = $item->quantity;
            }
        }

        return new StockPartition($confirmed, $held);
    }
}
