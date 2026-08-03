<x-app-layout>
    @section('title', 'My Orders')

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-10 lg:py-14">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-3">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">My Orders</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">My Orders</h1>
            <p class="text-market-200 mt-1">View and track all your orders</p>
        </div>
    </section>

    {{-- Orders List --}}
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            @if($orders->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50">
                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Order</th>
                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Date</th>
                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Status</th>
                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Items</th>
                                    <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Total</th>
                                    <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($orders as $order)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('orders.show', $order->id) }}" class="font-medium text-market-600 dark:text-market-400 hover:underline">
                                                #{{ $order->order_number ?? $order->id }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $order->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4">
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
                                            <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold {{ $statusColor }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $order->items_count ?? $order->items->sum('quantity') }} items
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                            ${{ number_format($order->total, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">
                                                View Details
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-8">
                    {{ $orders->links() }}
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-16 lg:py-24">
                    <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No orders yet</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">You haven't placed any orders yet. Start shopping and your orders will appear here.</p>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-market-600/20">
                        Start Shopping
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
