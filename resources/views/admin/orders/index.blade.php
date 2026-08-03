<x-app-layout>
    @section('title', 'Orders')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Orders</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all marketplace orders</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <input type="text" name="search" placeholder="Search orders..." value="{{ request('search') }}"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500 w-48">
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => ($sortField === 'created_at' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                                                Order
                                                @if($sortField === 'created_at')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-5 py-3">Customer</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total', 'dir' => ($sortField === 'total' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">
                                                Total
                                                @if($sortField === 'total')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'updated_at', 'dir' => ($sortField === 'updated_at' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">
                                                Updated
                                                @if($sortField === 'updated_at')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($orders as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">#{{ $order->order_number }}</a>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M d, Y g:i A') }}</div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->user?->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->user?->email }}</div>
                                            </td>
                                            <td class="px-5 py-4">
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
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($order->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($order->total, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $order->updated_at->diffForHumans() }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-market-600 dark:text-market-400 bg-market-50 dark:bg-market-900/20 hover:bg-market-100 dark:hover:bg-market-900/30 rounded-lg transition-colors">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No orders found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
