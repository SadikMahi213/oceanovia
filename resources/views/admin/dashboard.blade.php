<x-app-layout>
    @section('title', 'Admin Dashboard')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Welcome back, {{ auth()->user()->name }}</p>
                    </div>

                    {{-- ===== SECTION 1: Quick Action Cards (all sections with counts) ===== --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
                        <a href="{{ url('admin/orders') }}" class="relative flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-blue-200 dark:hover:border-blue-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Orders</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $totalOrders }} total · <span class="text-yellow-600 dark:text-yellow-400">{{ $pendingOrders }} pending</span></p>
                            </div>
                        </a>
                        <a href="{{ url('admin/products') }}" class="relative flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-green-200 dark:hover:border-green-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Products</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $publishedProducts }} published · <span class="text-red-600 dark:text-red-400">{{ $outOfStockProductsCount }} out of stock</span></p>
                            </div>
                        </a>
                        <a href="{{ url('admin/categories') }}" class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-purple-200 dark:hover:border-purple-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Categories</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Organize your catalog</p>
                            </div>
                        </a>
                        <a href="{{ url('admin/reviews') }}" class="relative flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-yellow-200 dark:hover:border-yellow-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Reviews</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><span class="text-yellow-600 dark:text-yellow-400">{{ $pendingReviews }} pending</span> moderation</p>
                            </div>
                        </a>
                        <a href="{{ url('admin/users') }}" class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m0 0A5.99 5.99 0 0115 21"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Users</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $totalUsers }} total · {{ $totalSellers }} sellers · {{ $totalSuppliers }} suppliers</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.payouts.index') }}" class="relative flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-emerald-200 dark:hover:border-emerald-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Payouts</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($pendingPayouts, 2) }} pending</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.refunds.index') }}" class="relative flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-red-200 dark:hover:border-red-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Refunds</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><span class="text-red-600 dark:text-red-400">{{ $pendingRefundsCount }} pending</span> · {{ $totalRefunds }} total</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-pink-200 dark:hover:border-pink-700 transition-all group">
                            <div class="w-10 h-10 rounded-lg bg-pink-50 dark:bg-pink-900/20 flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Coupons</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activeCoupons }} active</p>
                            </div>
                        </a>
                    </div>

                    {{-- ===== SECTION 2: Pending Items Needing Attention ===== --}}
                    @if($pendingOrders > 0 || $pendingRefundsCount > 0 || $pendingReviews > 0 || $pendingPayoutsCount > 0 || $outOfStockProductsCount > 0)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 mb-8">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Needs Attention
                            </h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                                @if($pendingOrders > 0)
                                    <a href="{{ url('admin/orders?status=pending') }}" class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors">
                                        <span class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-700 dark:text-yellow-400 font-bold text-sm">{{ $pendingOrders }}</span>
                                        <span class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Pending Orders</span>
                                    </a>
                                @endif
                                @if($pendingRefundsCount > 0)
                                    <a href="{{ route('admin.refunds.index', ['status' => 'pending']) }}" class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                                        <span class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-700 dark:text-red-400 font-bold text-sm">{{ $pendingRefundsCount }}</span>
                                        <span class="text-sm font-medium text-red-800 dark:text-red-300">Pending Refunds</span>
                                    </a>
                                @endif
                                @if($pendingReviews > 0)
                                    <a href="{{ url('admin/reviews?status=pending') }}" class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                                        <span class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-700 dark:text-orange-400 font-bold text-sm">{{ $pendingReviews }}</span>
                                        <span class="text-sm font-medium text-orange-800 dark:text-orange-300">Pending Reviews</span>
                                    </a>
                                @endif
                                @if($pendingPayoutsCount > 0)
                                    <a href="{{ route('admin.payouts.index', ['status' => 'pending']) }}" class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                                        <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-400 font-bold text-sm">{{ $pendingPayoutsCount }}</span>
                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Pending Payouts</span>
                                    </a>
                                @endif
                                @if($outOfStockProductsCount > 0)
                                    <a href="{{ url('admin/products?stock=out') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-700 dark:text-gray-300 font-bold text-sm">{{ $outOfStockProductsCount }}</span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-300">Out of Stock</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- ===== SECTION 3: Stats Overview ===== --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($totalRevenue, 2) }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ $completedOrders }}</span> completed orders
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orders</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalOrders }}</p>
                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span><span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ $pendingOrders }}</span> pending</span>
                                <span><span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $processingOrders }}</span> processing</span>
                                <span><span class="text-red-600 dark:text-red-400 font-medium">{{ $cancelledOrders }}</span> cancelled</span>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Products</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalProducts }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ $publishedProducts }}</span> published
                                <span class="text-red-600 dark:text-red-400 font-medium">{{ $outOfStockProductsCount }}</span> out of stock
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Community</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalUsers }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="text-blue-600 dark:text-blue-400 font-medium">{{ $totalSellers }}</span> sellers
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $totalSuppliers }}</span> suppliers
                            </div>
                        </div>
                    </div>

                    {{-- ===== SECTION 4: Recent Activity (2-column grid) ===== --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        {{-- Recent Orders --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                                <a href="{{ url('admin/orders') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">View All</a>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($recentOrders as $order)
                                    <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">#{{ $order->order_number }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $order->user?->name }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($order->total, 2) }}</span>
                                            @php
                                                $badge = match($order->status) {
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                                    'processing' => 'bg-indigo-100 text-indigo-800',
                                                    'shipped' => 'bg-purple-100 text-purple-800',
                                                    'delivered' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800',
                                                    default => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($order->status) }}</span>
                                        </div>
                                    </a>
                                @empty
                                    <p class="px-5 py-8 text-sm text-gray-500 dark:text-gray-400 text-center">No orders yet.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Recent Reviews --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent Reviews</h2>
                                <a href="{{ url('admin/reviews') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">View All</a>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($recentReviews as $review)
                                    <div class="px-5 py-3">
                                        <div class="flex items-start justify-between">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $review->product?->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">by {{ $review->user?->name }}</p>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                @endfor
                                            </div>
                                        </div>
                                        @if($review->body)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ Str::limit($review->body, 80) }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="px-5 py-8 text-sm text-gray-500 dark:text-gray-400 text-center">No reviews yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- ===== SECTION 5: Top Sellers & Recent Refunds ===== --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Top Sellers --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Top Sellers by Payout</h2>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($topSellers as $payout)
                                    <div class="flex items-center justify-between px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-market-500 to-market-700 flex items-center justify-center text-white font-bold text-xs">{{ substr($payout->seller?->name ?? '?', 0, 1) }}</div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->seller?->name ?? 'Deleted Seller' }}</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($payout->total_paid, 2) }}</span>
                                    </div>
                                @empty
                                    <p class="px-5 py-8 text-sm text-gray-500 dark:text-gray-400 text-center">No completed payouts yet.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Recent Refunds --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recent Refunds</h2>
                                <a href="{{ route('admin.refunds.index') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">View All</a>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($recentRefunds as $refund)
                                    <a href="{{ route('admin.refunds.show', $refund) }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $refund->user?->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($refund->reason, 40) }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($refund->amount, 2) }}</span>
                                            @php
                                                $rBadge = match($refund->status) {
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                    default => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $rBadge }}">{{ ucfirst($refund->status) }}</span>
                                        </div>
                                    </a>
                                @empty
                                    <p class="px-5 py-8 text-sm text-gray-500 dark:text-gray-400 text-center">No refunds yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
