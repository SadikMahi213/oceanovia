<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'seller', 'inventory']);

        if ($request->filled('search')) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$q->search}%")
                  ->orWhere('sku', 'like', "%{$q->search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stock')) {
            if ($request->stock === 'low') {
                $query->whereHas('inventory', fn($q) => $q->whereColumn('stock_quantity', '<=', 'stock_alert_threshold'));
            } elseif ($request->stock === 'out') {
                $query->whereHas('inventory', fn($q) => $q->where('stock_quantity', 0));
            } elseif ($request->stock === 'in') {
                $query->whereHas('inventory', fn($q) => $q->where('stock_quantity', '>', 0));
            }
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured === '1');
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowed = ['name', 'price', 'status', 'total_sold', 'created_at'];
        if (!in_array($sortField, $allowed)) $sortField = 'created_at';
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $products = $query->paginate(15);
        $categories = Category::active()->ordered()->get();

        return view('admin.products.index', compact('products', 'categories', 'sortField', 'sortDir'));
    }
}
