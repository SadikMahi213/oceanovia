<x-app-layout>
    <section class="py-12 bg-white dark:bg-gray-950">
        <div class="max-w-2xl mx-auto px-4">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Thank you for your order!</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Your order <strong class="text-gray-900 dark:text-white">{{ $order->order_number }}</strong> has been placed successfully.</p>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Order Summary</h3>
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->subtotal, 2) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="space-y-2 text-sm mb-8">
                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                    <span>Subtotal</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->shipping_cost > 0)
                    <div class="flex justify-between text-gray-500 dark:text-gray-400">
                        <span>Shipping</span>
                        <span>${{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                @endif
                @if($order->tax > 0)
                    <div class="flex justify-between text-gray-500 dark:text-gray-400">
                        <span>Tax</span>
                        <span>${{ number_format($order->tax, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700 pt-2">
                    <span>Total</span>
                    <span>${{ number_format($order->total, 2) }}</span>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">
                    View Order Details
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
