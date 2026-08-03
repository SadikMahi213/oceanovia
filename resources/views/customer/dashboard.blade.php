<x-app-layout>
    @section('title', 'Dashboard')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Welcome back, {{ $user->name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-market-50 dark:bg-market-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ordersCount }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Total Orders</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $activeOrders }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Active Orders</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $deliveredOrders }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Delivered</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-pink-50 dark:bg-pink-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $wishlistCount }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Wishlist</p>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $couponCount }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Coupons</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                        <div class="lg:col-span-2">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                                    <a href="{{ route('customer.orders.index') }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">View All</a>
                                </div>
                                @if($recentOrders->count() > 0)
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="bg-gray-50 dark:bg-gray-700/50">
                                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Order #</th>
                                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Date</th>
                                                    <th class="text-left px-6 py-4 font-semibold text-gray-900 dark:text-white">Status</th>
                                                    <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Total</th>
                                                    <th class="text-right px-6 py-4 font-semibold text-gray-900 dark:text-white">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach($recentOrders as $order)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                        <td class="px-6 py-4 font-medium text-market-600 dark:text-market-400">#{{ $order->order_number ?? $order->id }}</td>
                                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
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
                                                            <span class="inline-flex px-3 py-1 rounded-lg text-xs font-semibold {{ $statusColor }}">{{ ucfirst($order->status) }}</span>
                                                        </td>
                                                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">${{ number_format($order->total, 2) }}</td>
                                                        <td class="px-6 py-4 text-right">
                                                            <a href="{{ route('customer.orders.show', $order->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">
                                                                View
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-12">
                                        <p class="text-gray-500 dark:text-gray-400">No orders yet</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="lg:col-span-1">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        Notifications
                                        @if($unreadNotifications > 0)
                                            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-market-600 rounded-full">{{ $unreadNotifications }}</span>
                                        @endif
                                    </h2>
                                    <a href="{{ route('customer.notifications.index') }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">View All</a>
                                </div>
                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($notifications as $notification)
                                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $notification->read_at ? '' : 'bg-market-50/50 dark:bg-market-900/10' }}">
                                            <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $notification->title ?? 'Notification' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    @empty
                                        <div class="text-center py-12">
                                            <p class="text-gray-500 dark:text-gray-400">No notifications</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($recentlyViewed && $recentlyViewed->count() > 0)
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recently Viewed</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($recentlyViewed as $item)
                                    @include('components.product-card', ['product' => $item->product ?? $item])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($recommendedProducts && $recommendedProducts->count() > 0)
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recommended Products</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($recommendedProducts as $product)
                                    @include('components.product-card', ['product' => $product])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
