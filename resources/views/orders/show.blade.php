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
@endphp

<x-app-layout>
    @section('title', 'Order #' . ($order->order_number ?? $order->id))

    {{-- Breadcrumb --}}
    <section class="bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('orders.index') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">My Orders</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 dark:text-white font-medium">Order #{{ $order->order_number ?? $order->id }}</span>
            </nav>
        </div>
    </section>

    {{-- Order Header --}}
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

    {{-- Order Content --}}
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
                {{-- Left: Items --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Items Table --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Product</th>
                                        <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Seller</th>
                                        <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">SKU</th>
                                        <th class="text-center px-6 py-4 font-semibold text-gray-900 dark:text-white">Qty</th>
                                        <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Price</th>
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
                                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $item->seller?->name ?? ($item->seller_name ?? '—') }}</td>
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

                    {{-- Addresses --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        {{-- Shipping Address --}}
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
                        {{-- Billing Address --}}
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
                                <p class="text-sm text-gray-400">No billing address available</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right: Totals --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8 sticky top-28 space-y-6">
                        {{-- Totals --}}
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Order Summary</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($order->subtotal, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                    <span class="font-medium {{ $order->shipping_cost > 0 ? 'text-gray-900 dark:text-white' : 'text-green-600 dark:text-green-400' }}">
                                        @if($order->shipping_cost > 0)
                                            ${{ number_format($order->shipping_cost, 2) }}
                                        @else
                                            Free
                                        @endif
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($order->tax, 2) }}</span>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                                        <span class="text-xl font-bold text-market-600 dark:text-market-400">${{ number_format($order->total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Info --}}
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Payment</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Method</span>
                                    <span class="font-medium text-gray-900 dark:text-white capitalize">{{ $order->payment_method ?? '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-semibold {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                        {{ ucfirst($order->payment_status ?? 'pending') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6 space-y-3">
                            <a href="{{ route('orders.index') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                All Orders
                            </a>
                            <a href="{{ route('products.index') }}" class="w-full inline-flex items-center justify-center gap-2 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                Shop Again
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
