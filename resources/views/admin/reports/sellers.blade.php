<x-app-layout>
    @section('title', 'Seller Performance')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Seller Performance</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Metrics and performance for all sellers</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.reports.sellers') }}" class="mb-6">
                        <div class="flex gap-3">
                            <input type="text" name="search" placeholder="Search sellers..." value="{{ request('search') }}" class="flex-1 rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            <button type="submit" class="px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Search</button>
                        </div>
                    </form>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Seller</th>
                                        <th class="px-5 py-3">Store</th>
                                        <th class="px-5 py-3 text-right">Products</th>
                                        <th class="px-5 py-3 text-right">Orders</th>
                                        <th class="px-5 py-3 text-right">Revenue</th>
                                        <th class="px-5 py-3 text-right">Commission</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($sellers as $seller)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-market-500 to-market-700 rounded-lg flex items-center justify-center">
                                                        <span class="text-white font-bold text-sm">{{ substr($seller->name, 0, 1) }}</span>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $seller->name }} {{ $seller->lastname }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $seller->email }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $seller->sellerProfile?->store_name ?? '—' }}
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">{{ $seller->products_count }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">{{ $seller->total_orders }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">${{ number_format($seller->total_revenue, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">${{ number_format($seller->commission_earned, 2) }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $seller->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                                    {{ ucfirst($seller->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No sellers found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($sellers->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $sellers->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
