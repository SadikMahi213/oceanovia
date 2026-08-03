<x-app-layout>
    @section('title', 'Request Refund')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <div class="flex-1 min-w-0 max-w-2xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Request Refund</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Order {{ $order->order_number }}</p>
                        </div>
                        <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Summary</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Order Number</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Order Date</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Order Status</span>
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ match($order->status_color) { 'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400', 'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' } }}">{{ $order->status_label }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Order Total</span>
                                <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($order->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @if($order->items && $order->items->count() > 0)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Select Item to Refund</h2>
                            </div>
                            <div class="p-5 space-y-3">
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <input type="radio" name="refund_item" value="all" checked
                                        @change="itemId = ''; amount = {{ $order->total }}"
                                        class="text-market-600 focus:ring-market-500">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Entire Order</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Refund the full order amount</p>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($order->total, 2) }}</span>
                                </label>
                                @foreach($order->items as $item)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <input type="radio" name="refund_item" value="{{ $item->id }}"
                                            @change="itemId = {{ $item->id }}; amount = {{ $item->subtotal }}"
                                            class="text-market-600 focus:ring-market-500">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $item->product_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ $item->quantity }} &middot; ${{ number_format($item->unit_price, 2) }} ea</p>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->subtotal, 2) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">Please fix the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('refunds.store', $order) }}" class="space-y-6" x-data="{ itemId: '', amount: {{ $order->total }} }">
                        @csrf
                        <input type="hidden" name="order_item_id" x-model="itemId">
                        <input type="hidden" name="amount" x-model="amount">

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Refund Reason</h2>
                            </div>
                            <div class="p-5">
                                <div>
                                    <x-input-label for="reason" value="Reason for Refund *" />
                                    <textarea id="reason" name="reason" rows="4" required
                                        placeholder="Please describe why you are requesting a refund..."
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('reason') }}</textarea>
                                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Submit Refund Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
