<x-app-layout>
    @section('title', 'COD Collection #'.$collection->id)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <a href="{{ route('admin.cod-collections.index') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline inline-flex items-center gap-1 mb-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back to COD Collections
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">COD Collection</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Order
                            <a href="{{ route('admin.orders.show', $collection->order_id) }}" class="text-market-600 dark:text-market-400 hover:underline">{{ $collection->order?->order_number }}</a>
                        </p>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-800 dark:text-green-300">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-800 dark:text-red-300">{{ session('error') }}</div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Item</h2>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Product</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->orderItem?->product_name ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Quantity</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->orderItem?->quantity ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Seller</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->orderItem?->seller?->seller_display_name ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Supplier</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->orderItem?->supplier?->name ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Collection</h2>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Collector</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->collector?->name ?? '—' }} ({{ ucfirst($collection->collector_role) }})</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ ucfirst($collection->status) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Expected</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">${{ number_format($collection->expected_amount, 2) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Collected</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">${{ number_format($collection->collected_amount, 2) }}</dd>
                                    </div>
                                    @if((float) $collection->shortfall_amount > 0)
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400">Shortfall</dt>
                                            <dd class="text-red-600 dark:text-red-400 font-medium">${{ number_format($collection->shortfall_amount, 2) }}</dd>
                                        </div>
                                    @endif
                                    @if((float) $collection->overpaid_amount > 0)
                                        <div>
                                            <dt class="text-gray-500 dark:text-gray-400">Overpaid</dt>
                                            <dd class="text-amber-600 dark:text-amber-400 font-medium">${{ number_format($collection->overpaid_amount, 2) }}</dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Recorded by</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->collectedBy?->name ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">Recorded at</dt>
                                        <dd class="text-gray-900 dark:text-white font-medium">{{ $collection->collected_at?->format('M d, Y H:i') ?? '—' }}</dd>
                                    </div>
                                </dl>
                                @if($collection->notes)
                                    <div class="mt-4">
                                        <dt class="text-gray-500 dark:text-gray-400 text-sm">Notes</dt>
                                        <dd class="text-gray-900 dark:text-white text-sm mt-1">{{ $collection->notes }}</dd>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4">
                            @if($collection->status === 'pending')
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Record Collection</h3>
                                    <form method="POST" action="{{ route('admin.cod-collections.record', $collection) }}" class="space-y-4">
                                        @csrf
                                        <div>
                                            <x-input-label for="collected_amount" value="Amount Collected *" />
                                            <input id="collected_amount" name="collected_amount" type="number" step="0.01" min="0"
                                                value="{{ old('collected_amount', number_format((float) $collection->expected_amount, 2, '.', '')) }}" required
                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            @error('collected_amount')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Expected: ${{ number_format($collection->expected_amount, 2) }}</p>
                                        </div>
                                        <div>
                                            <x-input-label for="notes" value="Notes" />
                                            <textarea id="notes" name="notes" rows="3"
                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('notes') }}</textarea>
                                        </div>
                                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                            Record Collection
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">This collection has already been recorded.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
