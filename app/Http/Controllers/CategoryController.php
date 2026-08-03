<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()
            ->parents()
            ->ordered()
            ->with(['children' => function ($query) {
                $query->active()->ordered()->withCount(['products' => function ($q) {
                    $q->published();
                }]);
            }])
            ->withCount(['products' => function ($query) {
                $query->published();
            }])
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function show(string $slug, Request $request): View
    {
        $category = Category::active()->where('slug', $slug)->firstOrFail();

        $categoryIds = $category->children()
            ->active()
            ->pluck('id')
            ->push($category->id);

        $query = Product::published()
            ->whereIn('category_id', $categoryIds)
            ->with(['seller', 'category', 'inventory', 'reviews']);

        $sort = $request->sort;
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'best_selling':
                $query->orderBy('total_sold', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(12)->withQueryString();

        return view('categories.show', compact('category', 'products'));
    }
}
