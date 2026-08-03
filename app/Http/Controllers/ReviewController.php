<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:5000',
        ]);

        $hasPurchased = $product->orderItems()
            ->whereHas('order', fn ($q) => $q->byUser(auth()->id())->where('status', 'delivered'))
            ->exists();

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'body' => $request->body,
            'is_approved' => !$hasPurchased ? false : true,
            'is_featured' => false,
        ]);

        return redirect()->back()->with('success', 'Review submitted successfully!');
    }
}
