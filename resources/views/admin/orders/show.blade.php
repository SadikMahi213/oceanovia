<x-app-layout>
    @section('title', 'Order #'.$order->order_number)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <a href="{{ url('admin/orders') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline inline-flex items-center gap-1 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back to Orders
                            </a>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order #{{ $order->order_number }}</h1>
                        </div>
                        @php
                            $badge = match($order->status) {
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $badge }}">{{ ucfirst($order->status) }}</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Order Items --}}
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Order Items</h2>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                                <th class="px-5 py-3">Product</th>
                                                <th class="px-5 py-3 text-right">Price</th>
                                                <th class="px-5 py-3 text-right">Qty</th>
                                                <th class="px-5 py-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach($order->items as $item)
                                                <tr>
                                                    <td class="px-5 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                                @if($item->product?->thumbnail)
                                                                    <img src="{{ $item->product->thumbnail }}" alt="" class="w-full h-full object-cover">
                                                                @else
                                                                    <div class="w-full h-full flex items-center justify-center text-xs text-gray-400">—</div>
                                                                @endif
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $item->product?->name ?? $item->product_name ?? '—' }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">${{ number_format($item->price, 2) }}</td>
                                                    <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $item->quantity }}</td>
                                                    <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($item->subtotal, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-gray-50 dark:bg-gray-800/50">
                                            <tr>
                                                <td colspan="3" class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">Subtotal</td>
                                                <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($order->subtotal, 2) }}</td>
                                            </tr>
                                            @if($order->shipping_cost > 0)
                                                <tr>
                                                    <td colspan="3" class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">Shipping</td>
                                                    <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($order->shipping_cost, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if($order->tax > 0)
                                                <tr>
                                                    <td colspan="3" class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">Tax</td>
                                                    <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($order->tax, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if($order->discount > 0)
                                                <tr>
                                                    <td colspan="3" class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">Discount</td>
                                                    <td class="px-5 py-3 text-sm font-medium text-green-600 dark:text-green-400 text-right">-${{ number_format($order->discount, 2) }}</td>
                                                </tr>
                                            @endif
                                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                                <td colspan="3" class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">Total</td>
                                                <td class="px-5 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">${{ number_format($order->total, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Sidebar --}}
                        <div class="space-y-4">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Customer Details</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Name:</span> {{ $order->user?->name }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $order->user?->email }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Phone:</span> {{ $order->user?->phone ?? '—' }}</p>
                                </div>
                            </div>

                            @if($order->shippingAddress)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Shipping Address</h3>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                        <p>{{ $order->shippingAddress->address_line1 }}</p>
                                        @if($order->shippingAddress->address_line2)<p>{{ $order->shippingAddress->address_line2 }}</p>@endif
                                        <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->zip }}</p>
                                        <p>{{ $order->shippingAddress->country }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($order->billingAddress && $order->billingAddress->id !== $order->shippingAddress?->id)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Billing Address</h3>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                        <p>{{ $order->billingAddress->address_line1 }}</p>
                                        @if($order->billingAddress->address_line2)<p>{{ $order->billingAddress->address_line2 }}</p>@endif
                                        <p>{{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->zip }}</p>
                                        <p>{{ $order->billingAddress->country }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Payment</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Method:</span> {{ ucfirst($order->payment_method ?? '—') }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Status:</span>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                            {{ ucfirst($order->payment_status ?? 'pending') }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            @if($order->notes)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Notes</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $order->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
