<x-app-layout>
    @section('title', 'My Orders')

    @php
        $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
            'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        ];
        $tabs = ['all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $activeStatus = $status ?? 'all';
    @endphp

    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Orders</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View and track all your orders</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mb-6">
                        @foreach($tabs as $tab)
                            @php
                                $isActive = $activeStatus === $tab;
                                $count = $tab === 'all' ? $statusCounts['all'] : ($statusCounts[$tab] ?? 0);
                            @endphp
                            @if($isActive)
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-market-600 text-white text-sm font-medium rounded-xl">
                                    {{ ucfirst($tab) }}
                                    <span class="bg-white/20 text-white text-xs font-semibold px-2 py-0.5 rounded-full">{{ $count }}</span>
                                </span>
                            @else
                                <a href="{{ $tab === 'all' ? route('customer.orders.index') : route('customer.orders.filter', $tab) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                    {{ ucfirst($tab) }}
                                    <span class="bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-semibold px-2 py-0.5 rounded-full">{{ $count }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>

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
                                            @php
                                                $canCancel = in_array($order->status, ['pending', 'confirmed']);
                                                $canReorder = in_array($order->status, ['delivered', 'cancelled']);
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                <td class="px-6 py-4">
                                                    <a href="{{ route('customer.orders.show', $order->id) }}" class="font-medium text-market-600 dark:text-market-400 hover:underline">
                                                        #{{ $order->order_number ?? $order->id }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                                    {{ $order->created_at->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                                        {{ ucfirst($order->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                                    {{ $order->items->sum('quantity') }} items
                                                </td>
                                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                                    ${{ number_format($order->total, 2) }}
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        @if($canCancel)
                                                            <button type="button" onclick="document.getElementById('cancel-modal-{{ $order->id }}').classList.remove('hidden')" class="inline-flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                                                Cancel
                                                            </button>
                                                        @endif
                                                        @if($canReorder)
                                                            <form action="{{ route('customer.orders.reorder', $order->id) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="inline-flex items-center gap-1 text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">
                                                                    Reorder
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <a href="{{ route('customer.orders.invoice', $order->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                                            Invoice
                                                        </a>
                                                        <a href="{{ route('customer.orders.show', $order->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">
                                                            View Details
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>

                                            @if($canCancel)
                                                <div id="cancel-modal-{{ $order->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl">
                                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Cancel Order</h3>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Are you sure you want to cancel order #{{ $order->order_number ?? $order->id }}? This action cannot be undone.</p>
                                                        <form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST">
                                                            @csrf
                                                            <div class="mb-4">
                                                                <label for="reason-{{ $order->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason (optional)</label>
                                                                <textarea id="reason-{{ $order->id }}" name="reason" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-market-500 focus:border-transparent" placeholder="Tell us why you're cancelling..."></textarea>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-3">
                                                                <button type="button" onclick="document.getElementById('cancel-modal-{{ $order->id }}').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">Keep Order</button>
                                                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">Cancel Order</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-6">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-16 h-16 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <div>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">No orders yet</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">You haven't placed any orders yet. Start shopping and your orders will appear here.</p>
                                </div>
                                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors mt-2">
                                    Start Shopping
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
