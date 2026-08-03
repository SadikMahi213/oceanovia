<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->ordered()
            ->withCount(['products' => fn ($q) => $q->published()])
            ->get()
            ->map(fn ($cat) => array_merge($cat->toArray(), [
                'product_count' => $cat->product_count,
                'image_url' => $cat->image_url,
            ]));

        return response()->json(['data' => $categories]);
    }

    public function show($id): JsonResponse
    {
        $category = Category::active()
            ->with('children')
            ->withCount(['products' => fn ($q) => $q->published()])
            ->findOrFail($id);

        $products = $category->products()
            ->published()
            ->latest()
            ->paginate(12);

        return response()->json([
            'data' => array_merge($category->toArray(), [
                'product_count' => $category->product_count,
                'image_url' => $category->image_url,
                'products' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ]),
        ]);
    }
}
