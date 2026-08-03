<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::with('user', 'product');

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->whereNull('is_approved')->orWhere('is_approved', false);
            } elseif ($request->status === 'rejected') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $reviews = $query->latest()->paginate(15);

        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => true]);
        return back()->with('success', 'Review approved successfully.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => false]);
        return back()->with('success', 'Review rejected successfully.');
    }
}
