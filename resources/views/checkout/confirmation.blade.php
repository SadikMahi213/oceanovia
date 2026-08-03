<x-app-layout>
    @section('title', 'Order Confirmation')

    <section class="py-16 lg:py-24 bg-white dark:bg-gray-950">
        <div class="max-w-2xl mx-auto px-4">
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-8 lg:p-12 shadow-xl text-center">
                {{-- Success Animation --}}
                <div class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>

                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-3">Order Placed Successfully!</h1>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Thank you for your purchase. Your order has been placed and is being processed.</p>

                {{-- Order Number --}}
                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl mb-8">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Order Number:</span>
                    <span class="text-lg font-bold text-market-600 dark:text-market-400">{{ $order->order_number ?? $order->id }}</span>
                </div>

                {{-- Order Summary (minimal) --}}
                <div class="border-t border-gray-100 dark:border-gray-700 pt-6 mb-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">A confirmation email has been sent to <span class="font-medium text-gray-900 dark:text-white">{{ $order->user->email ?? Auth::user()->email }}</span></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">You can track your order status from your orders page.</p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('orders.show', $order->id) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        View Order
                    </a>
                    <a href="{{ route('products.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        Continue Shopping
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
