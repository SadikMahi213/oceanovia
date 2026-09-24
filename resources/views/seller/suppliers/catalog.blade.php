<x-app-layout>
    @section('title', 'Supplier Products')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Supplier Products</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Import wholesale products into your store. Stock is fulfilled by the supplier.</p>
                        </div>
                        <a href="{{ route('seller.suppliers.connected') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-market-700 dark:text-market-300 bg-market-50 dark:bg-market-900/30 hover:bg-market-100 dark:hover:bg-market-900/50 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            My Connected Products
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @forelse($supplierProducts as $sp)
                            @php
                                $isConnected = in_array($sp->id, $connected);
                                $sourceAvailable = $sp->available_quantity;
                            @endphp
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                                <div class="relative aspect-[4/3] bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    @if($sp->thumbnail)
                                        <img src="{{ asset('storage/'.$sp->images[0]) }}" alt="{{ $sp->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                    @if($sourceAvailable <= 0)
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                            <span class="px-3 py-1 rounded-full bg-red-500 text-white text-xs font-semibold">Out of Stock</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-5 flex-1 flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2">{{ $sp->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $sp->supplier?->supplierProfile?->company_name ?? 'Supplier' }}</p>
                                    <div class="flex items-center justify-between mt-3">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Wholesale</p>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($sp->wholesale_price, 2) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Available</p>
                                            <p class="text-sm font-medium {{ $sourceAvailable <= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $sourceAvailable }}</p>
                                        </div>
                                    </div>

                                    @if($isConnected)
                                        <div class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded-xl">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Already Imported
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('seller.suppliers.import') }}" class="mt-4 space-y-3">
                                            @csrf
                                            <input type="hidden" name="supplier_product_id" value="{{ $sp->id }}">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Your Retail Price</label>
                                                <input type="number" step="0.01" min="0.01" name="price" value="{{ number_format($sp->wholesale_price * 1.3, 2, '.', '') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm" {{ $sourceAvailable <= 0 ? 'disabled' : '' }}>
                                            </div>
                                            <button type="submit" {{ $sourceAvailable <= 0 ? 'disabled' : '' }} class="w-full px-4 py-2.5 text-sm font-medium text-white bg-market-600 hover:bg-market-700 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                Import to My Store
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="md:col-span-2 xl:col-span-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No supplier products available yet</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Check back later — suppliers are adding new products.</p>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if($supplierProducts->hasPages())
                        <div class="mt-6">
                            {{ $supplierProducts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>