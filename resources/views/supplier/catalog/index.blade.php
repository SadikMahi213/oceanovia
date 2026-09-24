<x-app-layout>
    @section('title', 'Source Products')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Source Products</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your wholesale catalog — stock is the single source of truth for sellers</p>
                        </div>
                        <a href="{{ route('supplier.catalog.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-market-600 hover:bg-market-700 rounded-xl shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Product
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">SKU</th>
                                        <th class="px-5 py-3 text-right">Wholesale</th>
                                        <th class="px-5 py-3 text-right">Stock</th>
                                        <th class="px-5 py-3 text-right">Reserved</th>
                                        <th class="px-5 py-3 text-right">Available</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($products as $product)
                                        @php
                                            $stock = $product->stock;
                                            $available = $stock?->available_quantity ?? 0;
                                            $status = $product->status === 'draft' ? 'draft' : ($available <= 0 ? 'out' : ($stock?->is_low_stock ? 'low' : 'in'));
                                            $statusColors = [
                                                'in' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'low' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'out' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                'draft' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                            ];
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($product->thumbnail)
                                                            <img src="{{ asset('storage/'.$product->images[0]) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[200px]">{{ $product->name }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->sku }}</td>
                                            <td class="px-5 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($product->wholesale_price, 2) }}</td>
                                            <td class="px-5 py-4 text-right text-sm text-gray-500 dark:text-gray-400">{{ $stock?->stock_quantity ?? 0 }}</td>
                                            <td class="px-5 py-4 text-right text-sm text-gray-500 dark:text-gray-400">{{ $stock?->reserved_quantity ?? 0 }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="text-sm font-medium {{ $available <= 0 ? 'text-red-600 dark:text-red-400' : ($stock?->is_low_stock ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white') }}">
                                                    {{ $available }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$status] }}">{{ ucfirst($status) }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('supplier.catalog.edit', $product) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        Edit
                                                    </a>
                                                    <button type="button" onclick="document.getElementById('stock-form-{{ $product->id }}').classList.toggle('hidden')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-market-700 dark:text-market-300 bg-market-50 dark:bg-market-900/30 hover:bg-market-100 dark:hover:bg-market-900/50 rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                                        Stock
                                                    </button>
                                                    <form method="POST" action="{{ route('supplier.catalog.destroy', $product) }}" onsubmit="return confirm('Delete this source product?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr id="stock-form-{{ $product->id }}" class="hidden bg-gray-50/50 dark:bg-gray-900/30">
                                            <td colspan="8" class="px-5 py-4">
                                                <form method="POST" action="{{ route('supplier.catalog.stock', $product) }}" class="flex flex-wrap items-end gap-3">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Stock Quantity</label>
                                                        <input type="number" name="stock_quantity" min="0" value="{{ $stock?->stock_quantity ?? 0 }}" class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Alert Threshold</label>
                                                        <input type="number" name="stock_alert_threshold" min="0" value="{{ $stock?->stock_alert_threshold ?? 5 }}" class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Warehouse</label>
                                                        <input type="text" name="warehouse_location" value="{{ $stock?->warehouse_location ?? '' }}" class="w-40 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                                    </div>
                                                    <button type="submit" class="px-4 py-2 text-xs font-medium text-white bg-market-600 hover:bg-market-700 rounded-lg transition-colors">Save Stock</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No source products yet</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add your first product so sellers can connect to it.</p>
                                                    </div>
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