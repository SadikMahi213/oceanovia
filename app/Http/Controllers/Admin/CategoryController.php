<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::with('parent');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active');
        }

        if ($request->filled('parent')) {
            $request->parent === 'none'
                ? $query->whereNull('parent_id')
                : $query->where('parent_id', $request->parent);
        }

        $sortField = $request->get('sort', 'sort_order');
        $sortDir = $request->get('dir', 'asc');
        $allowed = ['name', 'sort_order', 'status', 'products_count', 'created_at'];
        if (!in_array($sortField, $allowed)) $sortField = 'sort_order';
        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        $categories = $query->withCount('products')->paginate(15);
        $parentCategories = Category::parents()->ordered()->get();

        return view('admin.categories.index', compact('categories', 'parentCategories', 'sortField', 'sortDir'));
    }
}
