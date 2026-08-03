<x-app-layout>
    @section('title', 'Inventory Logs - ' . $product->name)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('supplier.inventory.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory Logs</h1>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                @if($product->thumbnail)
                                    <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $product->sku ?? 'N/A' }} | Current Stock: <span class="font-medium text-gray-900 dark:text-white">{{ $product->inventory?->stock_quantity ?? 0 }}</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Date / Time</th>
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
                                        @php
                                            $typeColors = [
                                                'adjustment' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                'damage' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                'return' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'transfer' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                            ];
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$log->type] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($log->type) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="text-sm font-medium {{ $log->quantity_change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $log->quantity_change >= 0 ? '+' : '' }}{{ $log->quantity_change }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $log->quantity_before }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right font-medium">{{ $log->quantity_after }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $log->notes ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->creator?->name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No logs recorded</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Inventory changes for this product will appear here</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($logs->hasPages())
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
