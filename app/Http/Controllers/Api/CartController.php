<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $cart = app(CartService::class)->findOrCreateForUser(auth()->id());
        $cart->load('items.product');

        return response()->json([
            'data' => [
                'cart_id' => $cart->id,
                'items' => $cart->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]),
                'total' => $cart->total,
                'items_count' => $cart->items_count,
            ],
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = Product::published()->findOrFail($validated['product_id']);

        $userId = auth()->id();

        // The mutation runs in a single transaction that takes the parent cart
        // row lock first, the same row the checkout path locks. A cart change
        // therefore cannot interleave with a checkout reading the same cart.
        $cart = DB::transaction(function () use ($userId, $product, $validated) {
            $cart = app(CartService::class)->lockForUser($userId);

            $existing = $cart->items()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->increment('quantity', $validated['quantity']);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $product->price,
                ]);
            }

            return $cart;
        }, CartService::TRANSACTION_ATTEMPTS);

        return $this->cartSummary($cart);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:0|max:100',
        ]);

        $userId = auth()->id();

        $cart = DB::transaction(function () use ($userId, $validated) {
            $cart = app(CartService::class)->lockForUser($userId);

            $item = $cart->items()
                ->where('id', $validated['cart_item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['quantity'] === 0) {
                $item->delete();
            } else {
                $item->update(['quantity' => $validated['quantity']]);
            }

            return $cart;
        }, CartService::TRANSACTION_ATTEMPTS);

        return $this->cartSummary($cart);
    }

    public function remove($id): JsonResponse
    {
        $userId = auth()->id();

        $cart = DB::transaction(function () use ($userId, $id) {
            $cart = app(CartService::class)->lockForUser($userId);

            $cart->items()
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();

            return $cart;
        }, CartService::TRANSACTION_ATTEMPTS);

        return $this->cartSummary($cart);
    }


    private function cartSummary(Cart $cart): JsonResponse
    {
        $cart->load('items.product');

        return response()->json([
            'data' => [
                'cart_id' => $cart->id,
                'items' => $cart->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]),
                'total' => $cart->total,
                'items_count' => $cart->items_count,
            ],
        ]);
    }
}
