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

    public function create(): View
    {
        $parentCategories = Category::parents()->ordered()->get();

        return view('admin.categories.form', ['category' => null, 'parentCategories' => $parentCategories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:categories,slug',
            'description'       => 'nullable|string|max:2000',
            'short_description' => 'nullable|string|max:500',
            'parent_id'         => 'nullable|exists:categories,id',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'sort_order'        => 'nullable|integer|min:0',
            'is_featured'       => 'sometimes|boolean',
            'status'            => 'sometimes|boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['status'] = $request->boolean('status', true);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $parentCategories = Category::where('id', '!=', $category->id)
            ->parents()->ordered()->get();

        return view('admin.categories.form', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description'       => 'nullable|string|max:2000',
            'short_description' => 'nullable|string|max:500',
            'parent_id'         => 'nullable|exists:categories,id',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'sort_order'        => 'nullable|integer|min:0',
            'is_featured'       => 'sometimes|boolean',
            'status'            => 'sometimes|boolean',
        ]);

        if ($validated['parent_id'] ?? null) {
            if ((int) $validated['parent_id'] === $category->id) {
                return back()->withErrors(['parent_id' => 'A category cannot be its own parent.'])->withInput();
            }
            $parent = Category::find($validated['parent_id']);
            if ($parent && $parent->parent_id === $category->id) {
                return back()->withErrors(['parent_id' => 'Circular parent relationship detected.'])->withInput();
            }
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['status'] = $request->boolean('status');

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with children or products.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
