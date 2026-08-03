<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
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

        $cart = $this->getOrCreateCart();

        $existing = $cart->items()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->increment('quantity', $validated['quantity']);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $product->price,
            ]);
        }

        return $this->cartSummary($cart);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:0|max:100',
        ]);

        $cart = $this->getOrCreateCart();
        $item = $cart->items()->findOrFail($validated['cart_item_id']);

        if ($validated['quantity'] === 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $validated['quantity']]);
        }

        return $this->cartSummary($cart);
    }

    public function remove($id): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->findOrFail($id)->delete();

        return $this->cartSummary($cart);
    }

    private function getOrCreateCart(): Cart
    {
        return Cart::firstOrCreate(['user_id' => auth()->id()]);
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
