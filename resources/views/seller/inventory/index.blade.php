<x-app-layout>
    @section('title', 'Inventory Management')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory Management</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track and adjust product stock levels</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">SKU</th>
                                        <th class="px-5 py-3 text-right">Current Stock</th>
                                        <th class="px-5 py-3 text-right">Low Stock Threshold</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Adjust Stock</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($inventory as $item)
                                        @php
                                            $product = $item->product;
                                            $stock = $item->stock_quantity ?? 0;
                                            $threshold = $item->low_stock_threshold ?? 5;
                                            if ($stock <= 0) {
                                                $stockStatus = 'out';
                                                $statusBadge = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                                            } elseif ($stock <= $threshold) {
                                                $stockStatus = 'low';
                                                $statusBadge = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
                                            } else {
                                                $stockStatus = 'in';
                                                $statusBadge = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($product?->thumbnail)
                                                            <img src="{{ $product->thumbnail }}" alt="{{ $product?->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[200px]">{{ $product?->name ?? '—' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $product?->sku ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">{{ $stock }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $threshold }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">
                                                    @if($stockStatus === 'in') In Stock
                                                    @elseif($stockStatus === 'low') Low Stock
                                                    @else Out of Stock
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <form method="POST" action="{{ route('seller.inventory.adjust', $product) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    <select name="type" required class="px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                                        <option value="adjustment">Set</option>
                                                        <option value="addition">Add</option>
                                                        <option value="removal">Remove</option>
                                                    </select>
                                                    <input type="number" name="quantity" placeholder="Qty" required class="w-16 px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                                    <input type="text" name="reason" placeholder="Reason" required class="w-28 px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-market-600 hover:bg-market-700 rounded-lg transition-colors">Update</button>
                                                </form>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('seller.inventory.logs', $product) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                                    View Logs
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No inventory items yet</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add products to your catalog to manage inventory</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($inventory, 'hasPages') && $inventory->hasPages())
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
