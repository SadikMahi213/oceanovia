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
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Add Category
                            </a>
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
                                        <th class="px-5 py-3 text-right">Actions</th>
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
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('admin.categories.edit', $cat) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')" class="inline">
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
                                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">No categories found.</td></tr>
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
