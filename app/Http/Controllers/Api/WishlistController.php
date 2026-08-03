<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(): JsonResponse
    {
        $wishlists = Wishlist::where('user_id', auth()->id())
            ->with('product')
            ->latest()
            ->get()
            ->pluck('product');

        return response()->json(['data' => $wishlists]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::published()->findOrFail($validated['product_id']);

        $existing = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $wished = false;
        } else {
            Wishlist::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]);
            $wished = true;
        }

        return response()->json([
            'data' => [
                'product_id' => (int) $product->id,
                'wished' => $wished,
            ],
        ]);
    }
}
