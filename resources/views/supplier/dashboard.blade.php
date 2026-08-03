<x-app-layout>
    @section('title', 'Supplier Dashboard')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Welcome back, {{ Auth::user()->name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Inventory</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalInventory) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-market-50 dark:bg-market-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Products</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($activeProducts) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Orders</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalOrders) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pending Orders</p>
                                    <p class="text-2xl font-bold {{ ($pendingOrders ?? 0) > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }} mt-1">{{ number_format($pendingOrders) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($totalRevenue, 2) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Wallet Balance</p>
                                    <p class="text-2xl font-bold text-market-600 dark:text-market-400 mt-1">${{ number_format($balance->balance, 2) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-market-50 dark:bg-market-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pending Settlement</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($pendingSettlements, 2) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-orange-50 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Low Stock Items</p>
                                    <p class="text-2xl font-bold {{ ($lowStockCount ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} mt-1">{{ number_format($lowStockCount) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-red-50 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Sales</h2>
                            </div>
                            <div class="p-5">
                                @php
                                    $maxSale = $monthlySales->max('total') ?: 1;
                                    $chartHeight = 180;
                                @endphp
                                <div class="flex items-end justify-between gap-2" style="height: {{ $chartHeight + 30 }}px">
                                    @forelse($monthlySales->reverse() as $sale)
                                        @php
                                            $barHeight = max(4, ($sale->total / $maxSale) * $chartHeight);
                                        @endphp
                                        <div class="flex flex-col items-center flex-1 min-w-0">
                                            <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 mb-1 truncate w-full text-center">${{ number_format($sale->total) }}</span>
                                            <div class="w-full bg-market-500 dark:bg-market-400 rounded-t-md transition-all hover:opacity-80 cursor-pointer" style="height: {{ $barHeight }}px"></div>
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate w-full text-center">{{ substr($sale->month, 5, 2) }}/{{ substr($sale->month, 2, 2) }}</span>
                                        </div>
                                    @empty
                                        <div class="w-full text-center text-sm text-gray-500 dark:text-gray-400 py-12">No sales data yet</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h2>
                            </div>
                            <div class="p-5 space-y-3">
                                <a href="{{ route('supplier.inventory.index') }}" class="flex items-center gap-3 p-3 bg-market-50 dark:bg-market-900/20 rounded-xl hover:bg-market-100 dark:hover:bg-market-900/30 transition-colors group">
                                    <div class="w-10 h-10 bg-market-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Manage Inventory</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Update stock levels and warehouse info</p>
                                    </div>
                                </a>
                                <a href="{{ route('supplier.orders.index') }}" class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">View Orders</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Process and fulfill customer orders</p>
                                    </div>
                                </a>
                                <a href="{{ route('supplier.returns.index') }}" class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors group">
                                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Handle Returns</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Manage return requests and refunds</p>
                                    </div>
                                </a>
                                <a href="{{ route('supplier.shipping.zones') }}" class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors group">
                                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Shipping Zones</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Configure shipping rates and zones</p>
                                    </div>
                                </a>
                                <a href="{{ route('supplier.kyc') }}" class="flex items-center gap-3 p-3 bg-teal-50 dark:bg-teal-900/20 rounded-xl hover:bg-teal-100 dark:hover:bg-teal-900/30 transition-colors group">
                                    <div class="w-10 h-10 bg-teal-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">KYC Verification</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Submit your verification documents</p>
                                    </div>
                                </a>
                                <a href="{{ route('supplier.profile') }}" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors group">
                                    <div class="w-10 h-10 bg-gray-500 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Company Profile</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Update your company details and settings</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Low Stock Alerts</h2>
                            </div>
                            <div class="p-5">
                                @if(isset($lowStockItems) && count($lowStockItems) > 0)
                                    <div class="space-y-3">
                                        @foreach($lowStockItems as $item)
                                            <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/10 rounded-xl border border-yellow-100 dark:border-yellow-900/30">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $item->product?->name ?? 'Unknown' }}</p>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0 ml-4">
                                                    <p class="text-sm font-medium text-yellow-700 dark:text-yellow-400">{{ $item->stock_quantity }} left</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Threshold: {{ $item->stock_alert_threshold }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-4 text-center">
                                        <a href="{{ route('supplier.inventory.index') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline">View Inventory →</a>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">All items are well stocked</p>
                                @endif
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Best Selling Products</h2>
                            </div>
                            <div class="p-5">
                                @if(isset($bestSellingProducts) && count($bestSellingProducts) > 0)
                                    <div class="space-y-3">
                                        @foreach($bestSellingProducts as $product)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/20 rounded-xl">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($product->product?->thumbnail)
                                                            <img src="{{ $product->product->thumbnail }}" alt="{{ $product->product->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center">
                                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[180px]">{{ $product->product?->name ?? 'Unknown' }}</p>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0 ml-4">
                                                    <p class="text-sm font-semibold text-market-600 dark:text-market-400">{{ $product->total_sold }} sold</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No sales yet</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                            <th class="px-5 py-3">Order #</th>
                                            <th class="px-5 py-3">Product</th>
                                            <th class="px-5 py-3 text-right">Qty</th>
                                            <th class="px-5 py-3">Status</th>
                                            <th class="px-5 py-3">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                'returned' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                            ];
                                        @endphp
                                        @forelse($recentOrders as $item)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                <td class="px-5 py-4">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->order?->order_number ?? '—' }}</span>
                                                </td>
                                                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[150px] truncate">{{ $item->product?->name ?? 'Unknown' }}</td>
                                                <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $item->quantity }}</td>
                                                <td class="px-5 py-4">
                                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $item->order?->status_label ?? ucfirst($item->status) }}</span>
                                                </td>
                                                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-5 py-12 text-center">
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No recent orders</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activities</h2>
                            </div>
                            <div class="p-5">
                                @if(isset($recentActivities) && count($recentActivities) > 0)
                                    <div class="space-y-4">
                                        @foreach($recentActivities as $activity)
                                            @php
                                                $typeIcon = match($activity->type) {
                                                    'adjustment' => '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>',
                                                    'damage' => '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                                    'return' => '<svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>',
                                                    'transfer' => '<svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
                                                    default => '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                                };
                                                $typeLabel = match($activity->type) {
                                                    'adjustment' => 'Adjustment',
                                                    'damage' => 'Damage',
                                                    'return' => 'Return',
                                                    'transfer' => 'Transfer',
                                                    default => ucfirst($activity->type),
                                                };
                                                $qtyClass = $activity->quantity_change < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                                                $qtyPrefix = $activity->quantity_change < 0 ? '' : '+';
                                            @endphp
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                                    {!! $typeIcon !!}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm text-gray-900 dark:text-white">
                                                        <span class="font-medium">{{ $typeLabel }}</span>
                                                        &mdash; {{ $activity->product?->name ?? 'Unknown' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        Qty: <span class="font-medium {{ $qtyClass }}">{{ $qtyPrefix }}{{ $activity->quantity_change }}</span>
                                                        &middot; {{ $activity->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent activities</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
