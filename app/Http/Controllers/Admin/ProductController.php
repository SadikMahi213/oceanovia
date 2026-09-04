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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
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

    public function create(): View
    {
        $categories = Category::active()->ordered()->get();

        return view('admin.products.form', ['product' => null, 'categories' => $categories]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:products,slug',
            'category_id'       => 'nullable|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'description'       => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price'             => 'required|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'cost_per_item'     => 'nullable|numeric|min:0',
            'sku'               => 'nullable|string|max:100|unique:products,sku',
            'barcode'           => 'nullable|string|max:100',
            'weight'            => 'nullable|numeric|min:0',
            'quantity'          => 'nullable|integer|min:0',
            'status'            => 'required|in:published,draft,archived',
            'is_featured'       => 'sometimes|boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['seller_id'] = auth()->id();

        $product = Product::create($validated);

        // Persist the Stock Quantity to the product's inventory row
        // (separate table). Create the row when missing.
        if ($request->filled('quantity')) {
            $product->inventory()->updateOrCreate(
                [],
                ['stock_quantity' => $request->integer('quantity')]
            );
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::active()->ordered()->get();

        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'category_id'       => 'nullable|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'description'       => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price'             => 'required|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'cost_per_item'     => 'nullable|numeric|min:0',
            'sku'               => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'barcode'           => 'nullable|string|max:100',
            'weight'            => 'nullable|numeric|min:0',
            'quantity'          => 'nullable|integer|min:0',
            'status'            => 'required|in:published,draft,archived',
            'is_featured'       => 'sometimes|boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');

        // Preserve seller ownership — admin editing should not change vendor
        unset($validated['seller_id']);

        $product->update($validated);

        // Persist the Stock Quantity to the product's inventory row
        // (separate table). Create the row when missing.
        if ($request->filled('quantity')) {
            $product->inventory()->updateOrCreate(
                [],
                ['stock_quantity' => $request->integer('quantity')]
            );
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): \Illuminate\Http\RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
