<x-app-layout>
    @section('title', 'Inventory Logs')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory Logs</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Stock adjustment history for {{ $product->name }}</p>
                        </div>
                        <a href="{{ route('seller.inventory.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Inventory
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $product->name }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $product->sku ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Stock</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $product->stock_quantity ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Date/Time</th>
                                        <th class="px-5 py-3">Type</th>
                                        <th class="px-5 py-3 text-right">Quantity Change</th>
                                        <th class="px-5 py-3 text-right">Previous Stock</th>
                                        <th class="px-5 py-3 text-right">New Stock</th>
                                        <th class="px-5 py-3">Reason</th>
                                        <th class="px-5 py-3">User</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($logs as $log)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $typeBadge = match($log->type) {
                                                        'adjustment' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                        'sale' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                        'return' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeBadge }}">{{ ucfirst($log->type) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-medium text-right {{ ($log->quantity_change ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ ($log->quantity_change ?? 0) >= 0 ? '+' : '' }}{{ $log->quantity_change }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 text-right">{{ $log->previous_stock }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 text-right">{{ $log->new_stock }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $log->reason ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->user?->name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No inventory logs yet</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Stock adjustments will appear here</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($logs, 'hasPages') && $logs->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $logs->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
