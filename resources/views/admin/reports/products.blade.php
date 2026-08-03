<x-app-layout>
    @section('title', 'Product Performance')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Product Performance</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Top selling products and their metrics</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.reports.products') }}" class="mb-6">
                        <div class="flex gap-3">
                            <select name="category_id" class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Filter</button>
                        </div>
                    </form>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">Seller</th>
                                        <th class="px-5 py-3">Category</th>
                                        <th class="px-5 py-3 text-right">Sold</th>
                                        <th class="px-5 py-3 text-right">Revenue</th>
                                        <th class="px-5 py-3 text-right">Avg Rating</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($products as $product)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3 max-w-[250px]">
                                                    @if($product->thumbnail)
                                                        <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover">
                                                    @else
                                                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                        </div>
                                                    @endif
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $product->seller?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $product->category?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">{{ $product->total_sold }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">${{ number_format($product->revenue, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">
                                                @if($product->avg_rating > 0)
                                                    {{ number_format($product->avg_rating, 1) }} / 5
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $product->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                                    {{ ucfirst($product->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No products found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($products->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $products->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
