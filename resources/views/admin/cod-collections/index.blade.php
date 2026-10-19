<x-app-layout>
    @section('title', 'COD Collections')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">COD Collections</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Verify cash collected on delivery per order item</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.cod-collections.export', request()->query()) }}"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:bg-gray-700 dark:hover:bg-gray-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Export CSV
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Records</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->total ?? 0) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">${{ number_format((float) ($summary->expected ?? 0), 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Collected</p>
                            <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">${{ number_format((float) ($summary->collected ?? 0), 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shortfall</p>
                            <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">${{ number_format((float) ($summary->shortfall ?? 0), 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Overpaid</p>
                            <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">${{ number_format((float) ($summary->overpaid ?? 0), 2) }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 mb-6">
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="collected" {{ request('status') === 'collected' ? 'selected' : '' }}>Collected</option>
                            </select>
                            <label class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                                <input type="checkbox" name="review" value="1" onchange="this.form.submit()" {{ request('review') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-market-600 focus:ring-market-500">
                                Needs review
                            </label>
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-market-600 hover:bg-market-700 rounded-xl">Filter</button>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Order</th>
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">Collector</th>
                                        <th class="px-5 py-3 text-right">Expected</th>
                                        <th class="px-5 py-3 text-right">Collected</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($collections as $collection)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm font-medium text-market-600 dark:text-market-400">
                                                <a href="{{ route('admin.cod-collections.show', $collection) }}" class="hover:underline">{{ $collection->order?->order_number ?? '—' }}</a>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white">{{ $collection->orderItem?->product_name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $collection->collector?->name ?? '—' }}
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($collection->collector_role) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">${{ number_format($collection->expected_amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">${{ number_format($collection->collected_amount, 2) }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $badge = match($collection->status) {
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'collected' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($collection->status) }}</span>
                                                @if($collection->requires_review)
                                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Review</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('admin.cod-collections.show', $collection) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">No COD collections</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Collection records appear here when a COD order is placed</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($collections->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $collections->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
