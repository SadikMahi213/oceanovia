@php
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    ];
    $statusColor = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';

    $timeline = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'complete'];
    $currentIndex = array_search($order->status, $timeline);
    if ($currentIndex === false) $currentIndex = -1;
@endphp

<x-app-layout>
    @section('title', 'Order #' . ($order->order_number ?? $order->id))

    <section class="bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('customer.orders.index') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">My Orders</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 dark:text-white font-medium">Order #{{ $order->order_number ?? $order->id }}</span>
            </nav>
        </div>
    </section>

    <section class="py-8 lg:py-10 bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">
                        Order #{{ $order->order_number ?? $order->id }}
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
                <span class="inline-flex px-4 py-1.5 rounded-lg text-sm font-semibold {{ $statusColor }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>
    </section>

    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="mb-10">
                <div class="flex items-center justify-between max-w-3xl mx-auto">
                    @foreach($timeline as $i => $step)
                        <div class="flex items-center">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors
                                    {{ $i < $currentIndex ? 'bg-green-500 border-green-500 text-white' : '' }}
                                    {{ $i === $currentIndex ? 'bg-market-600 border-market-600 text-white' : '' }}
                                    {{ $i > $currentIndex ? 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500' : '' }}">
                                    @if($i < $currentIndex)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                <span class="mt-2 text-xs font-medium
                                    {{ $i <= $currentIndex ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ ucfirst($step) }}
                                </span>
                            </div>
                            @if(!$loop->last)
                                <div class="w-12 sm:w-20 h-0.5 mx-2
                                    {{ $i < $currentIndex ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Product</th>
                                        <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">SKU</th>
                                        <th class="text-center px-6 py-4 font-semibold text-gray-900 dark:text-white">Qty</th>
                                        <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Unit Price</th>
                                        <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg shrink-0 flex items-center justify-center">
                                                        @if($item->product?->thumbnail)
                                                            <img src="{{ $item->product->thumbnail }}" alt="" class="w-full h-full object-cover rounded-lg">
                                                        @else
                                                            <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900 dark:text-white">{{ $item->product_name ?? $item->product?->name }}</p>
                                                        @if($item->product)
                                                            <a href="{{ route('products.show', $item->product->slug) }}" class="text-xs text-market-600 dark:text-market-400 hover:underline">View Product</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $item->sku ?? '—' }}</td>
                                            <td class="px-6 py-4 text-center text-gray-900 dark:text-white font-medium">{{ $item->quantity }}</td>
                                            <td class="px-6 py-4 text-right text-gray-900 dark:text-white">${{ number_format($item->price, 2) }}</td>
                                            <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">${{ number_format($item->price * $item->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Shipping Address</h3>
                            @if($order->shippingAddress)
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                    {{ $order->shippingAddress->first_name ?? $order->shippingAddress->name }}<br>
                                    {{ $order->shippingAddress->street }}<br>
                                    @if($order->shippingAddress->apt){{ $order->shippingAddress->apt }}<br>@endif
                                    {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->zip }}<br>
                                    @if($order->shippingAddress->phone){{ $order->shippingAddress->phone }}@endif
                                </p>
                            @else
                                <p class="text-sm text-gray-400">No shipping address available</p>
                            @endif
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Billing Address</h3>
                            @if($order->billingAddress)
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                    {{ $order->billingAddress->first_name ?? $order->billingAddress->name }}<br>
                                    {{ $order->billingAddress->street }}<br>
                                    @if($order->billingAddress->apt){{ $order->billingAddress->apt }}<br>@endif
                                    {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->zip }}
                                </p>
                            @else
                                <p class="text-sm text-gray-400">Same as shipping address</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8 sticky top-28 space-y-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Order Summary</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($order->subtotal, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                    <span class="font-medium {{ ($order->shipping_cost ?? 0) > 0 ? 'text-gray-900 dark:text-white' : 'text-green-600 dark:text-green-400' }}">
                                        @if(($order->shipping_cost ?? 0) > 0)
                                            ${{ number_format($order->shipping_cost, 2) }}
                                        @else
                                            Free
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($order->tax ?? 0, 2) }}</span>
                                </div>
                                @if($order->discount ?? false)
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Discount</span>
                                        <span class="font-medium text-green-600 dark:text-green-400">-${{ number_format($order->discount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                                        <span class="text-xl font-bold text-market-600 dark:text-market-400">${{ number_format($order->total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6 space-y-3">
                            @php
                                $canCancel = in_array($order->status, ['pending', 'confirmed']);
                                $canReorder = in_array($order->status, ['delivered', 'cancelled']);
                            @endphp
                            @if($canCancel)
                                <button type="button" onclick="document.getElementById('cancel-modal-show').classList.remove('hidden')" class="w-full inline-flex items-center justify-center gap-2 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-medium rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Cancel Order
                                </button>
                            @endif
                            @if($canReorder)
                                <form action="{{ route('customer.orders.reorder', $order->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Reorder
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('customer.orders.invoice', $order->id) }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download Invoice
                            </a>
                            <a href="{{ route('customer.orders.index') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                All Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Cancel Modal --}}
    <div id="cancel-modal-show" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Cancel Order</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Are you sure you want to cancel order #{{ $order->order_number ?? $order->id }}? This action cannot be undone.</p>
            <form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="cancel-reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason (optional)</label>
                    <textarea id="cancel-reason" name="reason" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-market-500 focus:border-transparent" placeholder="Tell us why you're cancelling..."></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="document.getElementById('cancel-modal-show').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">Keep Order</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
