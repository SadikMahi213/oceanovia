<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\RecentlyViewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function home(): View
    {
        $featuredProducts = Cache::remember('homepage.featured_products', 300, function () {
            return Product::published()
                ->featured()
                ->with(['seller', 'category', 'inventory', 'reviews'])
                ->latest()
                ->take(8)
                ->get();
        });

        $categories = Cache::remember('homepage.categories', 300, function () {
            return Category::active()
                ->parents()
                ->ordered()
                ->withCount(['products' => fn ($q) => $q->published()])
                ->get();
        });

        $banners = Cache::remember('homepage.banners', 300, function () {
            return \App\Models\Banner::where('status', true)
                ->orderBy('sort_order')
                ->get();
        });

        return view('welcome', compact('featuredProducts', 'categories', 'banners'));
    }

    public function index(Request $request): View
    {
        $query = Product::published()
            ->with(['seller', 'category', 'inventory', 'reviews']);

        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->firstOrFail();
            $categoryIds = Category::where('parent_id', $category->id)
                ->orWhere('id', $category->id)
                ->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->filled('seller')) {
            $query->bySeller((int) $request->seller);
        }

        if ($request->filled('min_price')) {
            $query->minPrice((float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->maxPrice((float) $request->max_price);
        }

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        $sort = $request->sort;
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'popular':
            case 'best_selling':
                $query->orderBy('total_sold', 'desc');
                break;
            case 'rating':
                $query->withAvg('reviews', 'rating')->orderBy('reviews_avg_rating', 'desc');
                break;
            case 'oldest':
                $query->oldest();
                break;
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::active()->parents()->ordered()->with('children')->withCount(['products' => fn ($q) => $q->published()])->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function sellers(): View
    {
        $sellers = \App\Models\User::ofType('seller')
            ->whereHas('sellerProfile', fn ($q) => $q->approved())
            ->with(['sellerProfile', 'products' => fn ($q) => $q->published()])
            ->withCount(['products' => fn ($q) => $q->published()])
            ->paginate(12);

        return view('products.sellers', compact('sellers'));
    }

    public function deals(): View
    {
        $products = Product::published()
            ->whereNotNull('compare_price')
            ->whereColumn('compare_price', '>', 'price')
            ->with(['seller', 'category', 'inventory'])
            ->latest()
            ->paginate(12);

        $categories = Category::active()->parents()->ordered()->with('children')->withCount(['products' => fn ($q) => $q->published()])->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function show(string $slug): View
    {
        $product = Product::published()
            ->where('slug', $slug)
            ->with([
                'seller.sellerProfile',
                'category',
                'inventory',
                'reviews.user',
            ])
            ->firstOrFail();

        $product->increment('total_views');

        if (auth()->check()) {
            RecentlyViewed::updateOrCreate(
                ['user_id' => auth()->id(), 'product_id' => $product->id],
                ['updated_at' => now()]
            );
        }

        $ratingCounts = [];
        $grouped = $product->reviews->where('is_approved', true)->groupBy('rating');
        for ($i = 1; $i <= 5; $i++) {
            $ratingCounts[$i] = $grouped->get($i, collect())->count();
        }

        return view('products.show', compact('product', 'ratingCounts'));
    }
}
