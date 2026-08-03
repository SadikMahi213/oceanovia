<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(): View
    {
        $products = collect();

        if (auth()->check()) {
            $productIds = Wishlist::where('user_id', auth()->id())
                ->latest()
                ->pluck('product_id');

            $products = Product::published()
                ->whereIn('id', $productIds)
                ->with(['seller', 'category', 'inventory'])
                ->get()
                ->sortBy(fn ($p) => $productIds->search($p->id));
        }

        return view('wishlist.index', compact('products'));
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $exists = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            Wishlist::where('user_id', auth()->id())
                ->where('product_id', $request->product_id)
                ->delete();
            $has = false;
        } else {
            Wishlist::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
            ]);
            $has = true;
        }

        return response()->json(['has' => $has]);
    }
}
