<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = null;
        $items = collect();

        if (auth()->check()) {
            $cart = Cart::with(['items.product.inventory', 'items.product.category'])
                ->where('user_id', auth()->id())
                ->first();
            $items = $cart?->items ?? collect();
        }

        return view('cart.index', compact('cart', 'items'));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:100',
        ]);

        $product = Product::published()->with('inventory')->findOrFail($request->product_id);
        $quantity = $request->integer('quantity', 1);

        if (! auth()->check()) {
            return response()->json([
                'cart_count' => $quantity,
                'total' => $product->price * $quantity,
            ]);
        }

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()], ['session_id' => null]);

        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ]);
        }

        $cart->load('items');
        $cart->refresh();

        return response()->json([
            'cart_count' => $cart->items_count,
            'total' => $cart->total,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem = CartItem::with('cart')
            ->whereHas('cart', fn ($q) => $q->where('user_id', auth()->id()))
            ->findOrFail($request->cart_item_id);

        $cartItem->update(['quantity' => $request->integer('quantity')]);

        $cart = $cartItem->cart->load('items');

        return response()->json([
            'cart_count' => $cart->items_count,
            'total' => $cart->total,
            'subtotal' => $cartItem->subtotal,
        ]);
    }

    public function remove(int $id): JsonResponse
    {
        $cart = Cart::where('user_id', auth()->id())->first();

        if (! $cart) {
            return response()->json(['cart_count' => 0, 'total' => 0]);
        }

        $cartItem = $cart->items()->where('id', $id)->first();
        if (! $cartItem) {
            $cartItem = $cart->items()->where('product_id', $id)->first();
        }

        if (! $cartItem) {
            $cart->load('items');

            return response()->json([
                'cart_count' => $cart->items_count,
                'total' => $cart->total,
            ]);
        }

        $cartItem->delete();
        $cart->load('items');

        return response()->json([
            'cart_count' => $cart->items_count,
            'total' => $cart->total,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['synced' => true]);
        }

        $request->validate([
            'items' => 'present|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ]);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()], ['session_id' => null]);

        $incomingIds = collect($request->items)->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Remove items that are no longer in the incoming list (persist deletions)
        if (empty($incomingIds)) {
            $cart->items()->delete();
        } else {
            $cart->items()->whereNotIn('product_id', $incomingIds)->delete();
        }

        foreach ($request->items as $item) {
            $product = Product::published()->find($item['id']);
            if (! $product) {
                continue;
            }

            $cartItem = $cart->items()->where('product_id', $product->id)->first();

            if ($cartItem) {
                $cartItem->update(['quantity' => $item['quantity'], 'unit_price' => $product->price]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ]);
            }
        }

        $cart->load('items.product.inventory', 'items.product.seller');

        return response()->json([
            'synced' => true,
            'cart_count' => $cart->items_count,
            'total' => $cart->total,
            'items' => $cart->items->map(fn ($i) => [
                'id' => $i->product_id,
                'cartItemId' => $i->id,
                'name' => $i->product->name,
                'price' => (float) $i->unit_price,
                'slug' => $i->product->slug,
                'image' => $i->product->image_url,
                'quantity' => $i->quantity,
                'sellerId' => $i->product->seller_id,
                'supplierId' => $i->product->inventory?->supplier_id,
                'weight' => (float) ($i->product->weight ?? 0),
            ])->values(),
        ]);
    }

    /**
     * Load the authenticated user's existing cart (used after login).
     *
     * Intentionally does NOT merge any guest/localStorage cart — the guest cart
     * is discarded and only the user's own persisted cart is returned.
     */
    public function load(): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['items' => []]);
        }

        $cart = Cart::with('items.product.inventory', 'items.product.seller')
            ->where('user_id', auth()->id())
            ->first();

        $items = $cart?->items?->filter(fn ($i) => $i->product)
            ->map(fn ($i) => [
                'id' => $i->product_id,
                'cartItemId' => $i->id,
                'name' => $i->product->name,
                'price' => (float) $i->unit_price,
                'slug' => $i->product->slug,
                'image' => $i->product->image_url,
                'quantity' => $i->quantity,
                'sellerId' => $i->product->seller_id,
                'supplierId' => $i->product->inventory?->supplier_id,
                'weight' => (float) ($i->product->weight ?? 0),
            ])->values() ?? [];

        return response()->json(['items' => $items]);
    }
}
