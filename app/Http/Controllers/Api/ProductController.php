<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::published()
            ->with(['seller', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        if ($request->filled('q')) {
            $query->search($request->string('q'));
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'popular' => $query->orderByDesc('total_sold'),
            'rating' => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
            default => $query->latest(),
        };

        $perPage = min($request->integer('per_page', 12), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->short_description,
                'price' => $product->price,
                'compare_price' => $product->compare_price,
                'thumbnail' => $product->thumbnail,
                'discount_percent' => $product->discount_percent,
                'rating_avg' => $product->rating_average,
                'reviews_count' => $product->reviews_count,
                'seller' => $product->seller?->name,
                'category' => $product->category?->name,
            ])->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $product = Product::with(['seller.sellerProfile', 'category', 'inventory', 'reviews' => function ($q) {
            $q->approved()->with('user');
        }])->findOrFail($id);

        $product->increment('total_views');

        return response()->json([
            'data' => array_merge($product->only([
                'id', 'name', 'slug', 'description', 'short_description', 'price',
                'compare_price', 'sku', 'category_id', 'brand_id', 'is_digital',
                'is_featured', 'unit', 'colors', 'sizes', 'tags', 'video_url',
            ]), [
                'rating_avg' => $product->rating_average,
                'reviews_count' => $product->reviews_count,
                'in_stock' => $product->in_stock,
                'stock_quantity' => $product->stock_quantity,
                'thumbnail' => $product->thumbnail,
                'image_urls' => $product->image_urls,
                'discount_percent' => $product->discount_percent,
                'category' => $product->category ? $product->category->only(['id', 'name', 'slug']) : null,
                'seller' => $product->seller ? [
                    'id' => $product->seller->id,
                    'name' => $product->seller->name,
                    'store_name' => $product->seller->sellerProfile?->store_name,
                    'avatar' => $product->seller->avatar_url,
                ] : null,
                'inventory' => $product->inventory ? [
                    'stock_quantity' => $product->inventory->stock_quantity,
                    'is_low_stock' => $product->inventory->is_low_stock,
                ] : null,
                'reviews' => $product->reviews->map(fn ($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'user' => [
                        'id' => $review->user?->id,
                        'name' => $review->user?->name,
                    ],
                ])->values(),
            ]),
        ]);
    }
}
