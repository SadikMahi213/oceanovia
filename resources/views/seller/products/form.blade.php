@php
$edit = isset($product);
$title = $edit ? 'Edit Product' : 'Add Product';
@endphp
<x-app-layout>
    @section('title', $title)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update product details' : 'Create a new product' }}</p>
                        </div>
                        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </a>
                    </div>

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">Please fix the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $edit ? route('seller.products.update', $product) : route('seller.products.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Basic Information</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="name" value="Product Name *" />
                                    <input type="text" id="name" name="name" value="{{ old('name', $product?->name) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="short_description" value="Short Description" />
                                    <textarea id="short_description" name="short_description" rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('short_description', $product?->short_description) }}</textarea>
                                    <x-input-error :messages="$errors->get('short_description')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="description" value="Full Description" />
                                    <textarea id="description" name="description" rows="6"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('description', $product?->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pricing</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="price" value="Price *" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', $product?->price) }}" required
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('price')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="compare_price" value="Compare Price" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="compare_price" name="compare_price" value="{{ old('compare_price', $product?->compare_price) }}"
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('compare_price')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="cost_per_item" value="Cost Per Item" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="cost_per_item" name="cost_per_item" value="{{ old('cost_per_item', $product?->cost_per_item) }}"
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('cost_per_item')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Inventory</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="sku" value="SKU *" />
                                    <input type="text" id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="barcode" value="Barcode (ISBN, UPC, etc.)" />
                                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product?->barcode) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('barcode')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="stock_quantity" value="Stock Quantity" />
                                    <input type="number" min="0" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('stock_quantity')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="stock_alert_threshold" value="Low Stock Alert Threshold" />
                                    <input type="number" min="0" id="stock_alert_threshold" name="stock_alert_threshold" value="{{ old('stock_alert_threshold', $product?->inventory?->stock_alert_threshold ?? 5) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('stock_alert_threshold')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Shipping</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <x-input-label for="weight" value="Weight (lbs)" />
                                    <input type="number" step="0.01" min="0" id="weight" name="weight" value="{{ old('weight', $product?->weight) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('weight')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="height" value="Height (in)" />
                                    <input type="number" step="0.01" min="0" id="height" name="height" value="{{ old('height', $product?->height) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('height')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="width" value="Width (in)" />
                                    <input type="number" step="0.01" min="0" id="width" name="width" value="{{ old('width', $product?->width) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('width')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="length" value="Length (in)" />
                                    <input type="number" step="0.01" min="0" id="length" name="length" value="{{ old('length', $product?->length) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('length')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Media</h2>
                            </div>
                            <div class="p-5">
                                <div>
                                    <x-input-label for="images" value="Product Images" />
                                    <input type="file" id="images" name="images[]" multiple accept="image/*"
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload multiple images. First image will be used as thumbnail.</p>
                                    <x-input-error :messages="$errors->get('images')" class="mt-1" />
                                    <x-input-error :messages="$errors->get('images.*')" class="mt-1" />
                                </div>
                                @if($edit && $product->images)
                                    <div class="mt-4 grid grid-cols-4 md:grid-cols-6 gap-3">
                                        @foreach($product->image_urls as $img)
                                            <div class="relative aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                                                <img src="{{ $img }}" alt="" class="w-full h-full object-cover">
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Attributes</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="colors" value="Colors" />
                                    <input type="text" id="colors" name="colors" value="{{ old('colors', $product ? implode(', ', $product->colors ?? []) : '') }}"
                                        placeholder="Red, Blue, Green"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated values</p>
                                    <x-input-error :messages="$errors->get('colors')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="sizes" value="Sizes" />
                                    <input type="text" id="sizes" name="sizes" value="{{ old('sizes', $product ? implode(', ', $product->sizes ?? []) : '') }}"
                                        placeholder="S, M, L, XL"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated values</p>
                                    <x-input-error :messages="$errors->get('sizes')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="tags" value="Tags" />
                                    <input type="text" id="tags" name="tags" value="{{ old('tags', $product ? implode(', ', $product->tags ?? []) : '') }}"
                                        placeholder="cotton, summer, sale"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated values</p>
                                    <x-input-error :messages="$errors->get('tags')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        {{-- Variants --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden" x-data="{ variants: {{ $product ? json_encode($product->variants->map(fn($v) => ['id' => $v->id, 'sku' => $v->sku, 'price' => $v->price, 'stock' => $v->stock_quantity, 'color' => $v->color, 'size' => $v->size, 'weight' => $v->weight])) : '[]' }} }">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Variants</h2>
                                <button type="button" @click="variants.push({ sku: '', price: '', stock: 0, color: '', size: '', weight: '' })" class="text-sm text-market-600 dark:text-market-400 hover:underline">+ Add Variant</button>
                            </div>
                            <div class="p-5">
                                <template x-for="(v, i) in variants" :key="i">
                                    <div class="grid grid-cols-6 gap-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl mb-2 items-end">
                                        <input type="hidden" :name="`variants[${i}][id]`" x-model="v.id">
                                        <div>
                                            <label class="text-xs text-gray-500">SKU</label>
                                            <input type="text" :name="`variants[${i}][sku]`" x-model="v.sku" class="w-full px-2 py-1.5 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Price</label>
                                            <input type="number" step="0.01" :name="`variants[${i}][price]`" x-model="v.price" class="w-full px-2 py-1.5 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Stock</label>
                                            <input type="number" :name="`variants[${i}][stock]`" x-model="v.stock" class="w-full px-2 py-1.5 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Color</label>
                                            <input type="text" :name="`variants[${i}][color]`" x-model="v.color" class="w-full px-2 py-1.5 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Size</label>
                                            <input type="text" :name="`variants[${i}][size]`" x-model="v.size" class="w-full px-2 py-1.5 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600">
                                        </div>
                                        <div>
                                            <button type="button" @click="variants.splice(i, 1)" class="w-full px-2 py-1.5 text-xs text-red-600 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40">Remove</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">SEO</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="meta_title" value="Meta Title" />
                                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $product?->meta_title) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('meta_title')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="meta_description" value="Meta Description" />
                                    <textarea id="meta_description" name="meta_description" rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('meta_description', $product?->meta_description) }}</textarea>
                                    <x-input-error :messages="$errors->get('meta_description')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Organization</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="category_id" value="Category *" />
                                    <select id="category_id" name="category_id" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="">Select a category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $product?->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="status" value="Status" />
                                    <select id="status" name="status"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="draft" {{ old('status', $product?->status ?? 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="published" {{ old('status', $product?->status) == 'published' ? 'selected' : '' }}>Published</option>
                                        <option value="archived" {{ old('status', $product?->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('seller.products.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Product' : 'Create Product' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
