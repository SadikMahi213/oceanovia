<x-app-layout>
    @section('title', 'Order ' . $order->order_number)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('seller.orders.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order {{ $order->order_number }}</h1>
                                <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-medium {{ match($order->status_color) { 'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400', 'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' } }}">{{ $order->status_label }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Placed on {{ $order->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        @if(in_array($order->status, ['processing', 'shipped']))
                            <form method="POST" action="{{ route('seller.orders.update-status', $order) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                @if($order->status === 'processing')
                                    <button type="submit" name="status" value="shipped" class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                        Mark as Shipped
                                    </button>
                                @endif
                                @if($order->status === 'shipped')
                                    <button type="submit" name="status" value="delivered" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Mark as Delivered
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Items</h2>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                                <th class="px-5 py-3">Product</th>
                                                <th class="px-5 py-3">SKU</th>
                                                <th class="px-5 py-3 text-right">Qty</th>
                                                <th class="px-5 py-3 text-right">Price</th>
                                                <th class="px-5 py-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @forelse($order->items->where('seller_id', Auth::id()) as $item)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td class="px-5 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                                @if($item->product?->thumbnail)
                                                                    <img src="{{ $item->product->thumbnail }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                                                @else
                                                                    <div class="w-full h-full flex items-center justify-center">
                                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->sku ?? '—' }}</td>
                                                    <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $item->quantity }}</td>
                                                    <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">${{ number_format($item->unit_price, 2) }}</td>
                                                    <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($item->subtotal, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No items from your store in this order</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if($order->items->where('seller_id', Auth::id())->count() > 0)
                                            <tfoot>
                                                <tr class="bg-gray-50 dark:bg-gray-800/50">
                                                    <td colspan="4" class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">Your Subtotal</td>
                                                    <td class="px-5 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">${{ number_format($order->items->where('seller_id', Auth::id())->sum('subtotal'), 2) }}</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Customer</h2>
                                </div>
                                <div class="p-5 space-y-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->user?->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->user?->email }}</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Shipping Address</h2>
                                </div>
                                <div class="p-5">
                                    @if($order->shippingAddress)
                                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->shippingAddress->full_name }}</p>
                                            <p>{{ $order->shippingAddress->address_line1 }}</p>
                                            @if($order->shippingAddress->address_line2)
                                                <p>{{ $order->shippingAddress->address_line2 }}</p>
                                            @endif
                                            <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->zip }}</p>
                                            <p>{{ $order->shippingAddress->country }}</p>
                                            @if($order->shippingAddress->phone)
                                                <p class="pt-1">{{ $order->shippingAddress->phone }}</p>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Not available</p>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Billing Address</h2>
                                </div>
                                <div class="p-5">
                                    @if($order->billingAddress)
                                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->billingAddress->full_name }}</p>
                                            <p>{{ $order->billingAddress->address_line1 }}</p>
                                            @if($order->billingAddress->address_line2)
                                                <p>{{ $order->billingAddress->address_line2 }}</p>
                                            @endif
                                            <p>{{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->zip }}</p>
                                            <p>{{ $order->billingAddress->country }}</p>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Same as shipping</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
