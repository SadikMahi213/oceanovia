<x-app-layout>
    @section('title', 'Products')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Products</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all marketplace products</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Add Product
                            </a>
                            <form method="GET" class="flex items-center gap-2 flex-wrap">
                            <input type="text" name="search" placeholder="Search name/SKU..." value="{{ request('search') }}" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500 w-40">
                            <select name="category_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <select name="status" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Status</option>
                                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                            <select name="stock" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Stock</option>
                                <option value="in" {{ request('stock') === 'in' ? 'selected' : '' }}>In Stock</option>
                                <option value="low" {{ request('stock') === 'low' ? 'selected' : '' }}>Low Stock</option>
                                <option value="out" {{ request('stock') === 'out' ? 'selected' : '' }}>Out of Stock</option>
                            </select>
                            <select name="featured" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All</option>
                                <option value="1" {{ request('featured') === '1' ? 'selected' : '' }}>Featured</option>
                                <option value="0" {{ request('featured') === '0' ? 'selected' : '' }}>Not Featured</option>
                            </select>
                            <button type="submit" hidden></button>
                        </form>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => ($sortField === 'name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">Product</a>
                                        </th>
                                        <th class="px-5 py-3">Seller</th>
                                        <th class="px-5 py-3">Category</th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'price', 'dir' => ($sortField === 'price' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">Price</a>
                                        </th>
                                        <th class="px-5 py-3 text-right">Stock</th>
                                        <th class="px-5 py-3 text-center">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'dir' => ($sortField === 'status' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-gray-900 dark:hover:text-white">Status</a>
                                        </th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_sold', 'dir' => ($sortField === 'total_sold' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">Sold</a>
                                        </th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($products as $product)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($product->thumbnail)
                                                            <img src="{{ $product->thumbnail }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center text-xs text-gray-400">—</div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[200px]">{{ $product->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ $product->sku }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->seller?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->category?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($product->price, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-right">
                                                @php $stock = $product->inventory?->stock_quantity ?? 0; $alert = $product->inventory?->stock_alert_threshold ?? 0; @endphp
                                                @if($stock === 0)
                                                    <span class="text-red-600 dark:text-red-400 font-medium">0</span>
                                                @elseif($stock <= $alert)
                                                    <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ $stock }}</span>
                                                @else
                                                    <span class="text-gray-900 dark:text-white">{{ $stock }}</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <x-inline-edit model="Product" :id="$product->id" field="status" :value="$product->status" type="select" :options="['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']" />
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $product->total_sold ?? 0 }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">No products found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($products->hasPages())<div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $products->links() }}</div>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
