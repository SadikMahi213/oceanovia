<x-app-layout>
    @section('title', $customer->name)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('supplier.customers.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $customer->name }}</h1>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Customer details and order history</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Customer Information</h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $customer->name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $customer->email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $customer->phone ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Spent</p>
                                    <p class="text-sm font-bold text-market-600 dark:text-market-400 mt-1">${{ number_format($totalSpent ?? 0, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Orders</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $orders->total() ?? $customer->orders?->count() ?? 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer Since</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $customer->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Order #</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Items</th>
                                        <th class="px-5 py-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($orders as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('supplier.orders.show', $order) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">{{ $order->order_number }}</a>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                        'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                        'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                    ];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $order->status_label ?? ucfirst($order->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $order->items_count ?? $order->items?->count() ?? 0 }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($order->subtotal ?? 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-12 text-center">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No orders from this customer</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>