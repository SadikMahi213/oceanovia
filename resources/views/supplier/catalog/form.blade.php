<x-app-layout>
    @section('title', $product ? 'Edit Source Product' : 'Add Source Product')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <a href="{{ route('supplier.catalog.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-market-600 transition-colors">← Back to Source Products</a>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $product ? 'Edit Source Product' : 'Add Source Product' }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sellers will see published products and purchase from your stock at the wholesale price.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                            <p class="text-sm font-medium text-red-700 dark:text-red-400">Please fix the following errors:</p>
                            <ul class="mt-1 list-disc list-inside text-xs text-red-600 dark:text-red-400">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $product ? route('supplier.catalog.update', $product) : route('supplier.catalog.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @if($product)
                            @method('PUT')
                        @endif

                        {{-- Basic info --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Product Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                    <input type="text" name="name" value="{{ old('name', $product?->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU *</label>
                                    <input type="text" name="sku" value="{{ old('sku', $product?->sku) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                                    <select name="category_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                        <option value="">Uncategorized</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand</label>
                                    <select name="brand_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                        <option value="">None</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id) == $brand->id)>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wholesale Price *</label>
                                    <input type="number" step="0.01" min="0" name="wholesale_price" value="{{ old('wholesale_price', $product?->wholesale_price) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Compare Price</label>
                                    <input type="number" step="0.01" min="0" name="compare_price" value="{{ old('compare_price', $product?->compare_price) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Barcode</label>
                                    <input type="text" name="barcode" value="{{ old('barcode', $product?->barcode) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                        <option value="published" @selected(old('status', $product?->status) === 'published')>Published</option>
                                        <option value="draft" @selected(old('status', $product?->status) === 'draft')>Draft</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Short Description</label>
                                    <input type="text" name="short_description" value="{{ old('short_description', $product?->short_description) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea name="description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">{{ old('description', $product?->description) }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Stock (only on create) --}}
                        @unless($product)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Initial Stock</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stock Quantity *</label>
                                        <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alert Threshold</label>
                                        <input type="number" min="0" name="stock_alert_threshold" value="{{ old('stock_alert_threshold', 5) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Warehouse Location</label>
                                        <input type="text" name="warehouse_location" value="{{ old('warehouse_location') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                    </div>
                                </div>
                            </div>
                        @endunless

                        {{-- Variants & dimensions --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Variants & Dimensions</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Material</label>
                                    <input type="text" name="material" value="{{ old('material', $product?->material) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit</label>
                                    <input type="text" name="unit" value="{{ old('unit', $product?->unit) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" placeholder="e.g. piece">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weight (kg)</label>
                                    <input type="number" step="0.01" min="0" name="weight" value="{{ old('weight', $product?->weight) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Height (cm)</label>
                                    <input type="number" step="0.01" min="0" name="height" value="{{ old('height', $product?->height) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Width (cm)</label>
                                    <input type="number" step="0.01" min="0" name="width" value="{{ old('width', $product?->width) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Length (cm)</label>
                                    <input type="number" step="0.01" min="0" name="length" value="{{ old('length', $product?->length) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Colors (comma separated)</label>
                                    <input type="text" name="colors" value="{{ old('colors', is_array($product?->colors) ? implode(', ', $product->colors) : $product?->colors) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sizes (comma separated)</label>
                                    <input type="text" name="sizes" value="{{ old('sizes', is_array($product?->sizes) ? implode(', ', $product->sizes) : $product?->sizes) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (comma separated)</label>
                                    <input type="text" name="tags" value="{{ old('tags', is_array($product?->tags) ? implode(', ', $product->tags) : $product?->tags) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- Images & SEO --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Images</h2>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Images</label>
                            <input type="file" name="images[]" multiple accept="image/*" class="block w-full text-sm text-gray-500 dark:text-gray-400">
                            <p class="text-xs text-gray-400 mt-1">JPEG, PNG or WebP. Multiple images allowed.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Title</label>
                                    <input type="text" name="meta_title" value="{{ old('meta_title', $product?->meta_title) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                                    <input type="text" name="meta_description" value="{{ old('meta_description', $product?->meta_description) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('supplier.catalog.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors">Cancel</a>
                            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-market-600 hover:bg-market-700 rounded-xl shadow-sm transition-colors">{{ $product ? 'Update Product' : 'Add Product' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>