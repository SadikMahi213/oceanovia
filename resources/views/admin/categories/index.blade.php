<x-app-layout>
    @section('title', 'Categories')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage product categories</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="parent" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Levels</option>
                                <option value="none" {{ request('parent') === 'none' ? 'selected' : '' }}>Parent Only</option>
                                @foreach($parentCategories as $pc)
                                    <option value="{{ $pc->id }}" {{ request('parent') == $pc->id ? 'selected' : '' }}>{{ $pc->name }}</option>
                                @endforeach
                            </select>
                            <select name="status" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <input type="text" name="search" placeholder="Search categories..." value="{{ request('search') }}" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500 w-44">
                            <button type="submit" hidden></button>
                        </form>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => ($sortField === 'name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">Name</a>
                                        </th>
                                        <th class="px-5 py-3">Parent</th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'products_count', 'dir' => ($sortField === 'products_count' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">Products</a>
                                        </th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'sort_order', 'dir' => ($sortField === 'sort_order' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">Order</a>
                                        </th>
                                        <th class="px-5 py-3 text-center">Status</th>
                                        <th class="px-5 py-3 text-right">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($categories as $cat)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    @if($cat->image_url)
                                                        <img src="{{ $cat->image_url }}" alt="" class="w-9 h-9 rounded-lg object-cover">
                                                    @else
                                                        <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <x-inline-edit model="Category" :id="$cat->id" field="name" :value="$cat->name" class="text-sm font-medium text-gray-900 dark:text-white" />
                                                        <p class="text-xs text-gray-500">/{{ $cat->slug }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $cat->parent?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $cat->products_count }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">
                                                <x-inline-edit model="Category" :id="$cat->id" field="sort_order" :value="$cat->sort_order" type="number" />
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <x-inline-edit model="Category" :id="$cat->id" field="status" :value="$cat->status" type="select" :options="[1 => 'Active', 0 => 'Inactive']" />
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $cat->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No categories found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($categories->hasPages())<div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $categories->links() }}</div>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
