<x-app-layout>
    @section('title', 'Inventory')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your product stock levels</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">SKU</th>
                                        <th class="px-5 py-3 text-right">Stock</th>
                                        <th class="px-5 py-3 text-right">Alert Threshold</th>
                                        <th class="px-5 py-3">Warehouse</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($inventory as $item)
                                        @php
                                            $status = $item->is_out_of_stock ? 'out' : ($item->is_low_stock ? 'low' : 'in');
                                            $statusColors = [
                                                'in' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'low' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'out' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                            ];
                                            $rowColor = match($status) {
                                                'in' => '',
                                                'low' => 'bg-yellow-50/50 dark:bg-yellow-900/5',
                                                'out' => 'bg-red-50/50 dark:bg-red-900/5',
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $rowColor }}">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($item->product?->thumbnail)
                                                            <img src="{{ $item->product->thumbnail }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[200px]">{{ $item->product?->name ?? 'Unknown Product' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->product?->sku ?? '—' }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="text-sm font-medium {{ $status === 'out' ? 'text-red-600 dark:text-red-400' : ($status === 'low' ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white') }}">
                                                    {{ $item->stock_quantity }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $item->stock_alert_threshold }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->warehouse_location ?? '—' }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$status] }}">{{ ucfirst($status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('supplier.inventory.edit', $item) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No inventory items</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Inventory will appear once products are assigned to you</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($inventory->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $inventory->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
