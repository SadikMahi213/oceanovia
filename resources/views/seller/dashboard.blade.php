<x-app-layout>
    @section('title', 'Seller Dashboard')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Welcome back, {{ Auth::user()->name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Products</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalProducts ?? 0 }}</p>
                                </div>
                                <div class="w-12 h-12 bg-market-50 dark:bg-market-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Orders</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalOrders ?? 0 }}</p>
                                </div>
                                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($totalRevenue ?? 0, 2) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Low Stock Items</p>
                                    <p class="text-2xl font-bold {{ ($lowStockProducts->count() ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} mt-1">{{ $lowStockProducts->count() ?? 0 }}</p>
                                </div>
                                <div class="w-12 h-12 bg-red-50 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sales Overview</h2>
                            </div>
                            <div class="p-5">
                                @if(isset($monthlySales) && count($monthlySales) > 0)
                                    <div class="relative h-48">
                                        <svg class="w-full h-full" viewBox="0 0 600 180" preserveAspectRatio="none">
                                            <polyline
                                                fill="none"
                                                stroke="url(#salesGradient)"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                points="{{ collect($monthlySales)->map(fn($m, $i) => (($i + 1) * 50) . ',' . (180 - ($m['total'] / (collect($monthlySales)->max('total') ?: 1) * 140)))->implode(' ') }}"
                                            />
                                            <defs>
                                                <linearGradient id="salesGradient" x1="0" y1="0" x2="1" y2="0">
                                                    <stop offset="0%" stop-color="#8b5cf6" />
                                                    <stop offset="100%" stop-color="#7c3aed" />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>
                                @else
                                    <div class="relative h-48 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-center">
                                        <svg class="w-full h-full" viewBox="0 0 600 180" preserveAspectRatio="none">
                                            <polyline fill="none" stroke="url(#salesGradient)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="50,150 150,120 250,130 350,80 450,100 550,60" />
                                            <defs>
                                                <linearGradient id="salesGradient" x1="0" y1="0" x2="1" y2="0">
                                                    <stop offset="0%" stop-color="#8b5cf6" />
                                                    <stop offset="100%" stop-color="#7c3aed" />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Low Stock Alerts</h2>
                            </div>
                            <div class="p-5">
                                @if(isset($lowStockProducts) && count($lowStockProducts) > 0)
                                    <ul class="space-y-3">
                                        @foreach($lowStockProducts as $product)
                                            <li class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-red-500 shrink-0"></div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product->stock_quantity }} in stock</p>
                                                </div>
                                                <a href="{{ route('seller.products.edit', $product) }}" class="text-xs text-market-600 dark:text-market-400 hover:underline shrink-0">Edit</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No low stock alerts</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                            <a href="{{ route('seller.orders.index') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline">View All</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <th class="px-5 py-3">Order</th>
                                        <th class="px-5 py-3">Customer</th>
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($recentOrders ?? [] as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('seller.orders.show', $order) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">{{ $order->order_number }}</a>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $order->user?->name }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($order->items->first()?->product_name ?? 'N/A', 30) }}</td>
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
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $order->status_label }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($order->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No orders yet</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
                            </div>
                            <div class="p-5 space-y-3">
                                <a href="{{ route('seller.products.create') }}" class="flex items-center gap-3 p-3 bg-market-50 dark:bg-market-900/20 rounded-xl hover:bg-market-100 dark:hover:bg-market-900/30 transition-colors group">
                                    <div class="w-10 h-10 bg-market-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Add New Product</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">List a new product in your store</p>
                                    </div>
                                </a>
                                <a href="{{ route('seller.orders.index') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors group">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">View Orders</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Manage and fulfill customer orders</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Status Distribution</h2>
                            </div>
                            <div class="p-5">
                                @php
                                    $blocks = [
                                        ['label' => 'Pending', 'count' => $pendingOrders ?? 0, 'color' => 'bg-yellow-500'],
                                        ['label' => 'Processing', 'count' => $processingOrders ?? 0, 'color' => 'bg-indigo-500'],
                                        ['label' => 'Shipped', 'count' => $shippedOrders ?? 0, 'color' => 'bg-purple-500'],
                                        ['label' => 'Delivered', 'count' => $deliveredOrders ?? 0, 'color' => 'bg-green-500'],
                                        ['label' => 'Cancelled', 'count' => $cancelledOrders ?? 0, 'color' => 'bg-red-500'],
                                    ];
                                    $totalBlock = collect($blocks)->sum('count');
                                @endphp
                                @if($totalBlock > 0)
                                    <div class="flex h-4 rounded-full overflow-hidden mb-4">
                                        @foreach($blocks as $b)
                                            @if($b['count'] > 0)
                                                <div class="{{ $b['color'] }}" style="width: {{ ($b['count'] / $totalBlock) * 100 }}%"></div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($blocks as $b)
                                            <div class="flex items-center justify-between text-sm">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full {{ $b['color'] }}"></span>
                                                    <span class="text-gray-600 dark:text-gray-400">{{ $b['label'] }}</span>
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $b['count'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No order data yet</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
