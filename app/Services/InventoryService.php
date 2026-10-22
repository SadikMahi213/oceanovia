<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Inventory;
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
        $this->sourceStock->reserveForCart($cart->items);

        // Collect the inventory rows this operation touches, then lock them
        // in a deterministic ascending order before making any decision that
        // depends on their current quantity. Two carts holding the same
        // products would otherwise lock the rows in whatever order their
        // items happen to be listed and can deadlock each other.
        $inventoryIds = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            // Sourced items reserve supplier source stock instead of the
            // per-listing inventory row; skip to avoid double-decrementing.
            if ($product->supplier_product_id) {
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

        // Re-check every quantity against the row we actually locked.
        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product || $product->supplier_product_id) {
                continue;
            }

            $inventory = $product->inventory;

            if (! $inventory) {
                continue;
            }

            $inventory = $locked[$inventory->id] ?? null;

            if (! $inventory) {
                continue;
            }

            if ($inventory->stock_quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Insufficient stock for \"{$product->name}\". Please adjust your cart.",
                ]);
            }

            $inventory->decrement('stock_quantity', $item->quantity);
        }
    }
}
