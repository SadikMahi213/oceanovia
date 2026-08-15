<x-app-layout>
    @section('title', 'Analytics')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your store's performance</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sales Overview</h2>
                            </div>
                            <div class="p-5">
                                <div class="relative h-64">
                                    <svg class="w-full h-full" viewBox="0 0 600 200" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.8" />
                                                <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.1" />
                                            </linearGradient>
                                        </defs>
                                        @php
                                            $salesData = $monthlySales ?? collect();
                                            $maxVal = $salesData->max('total') ?: 1;
                                            $barCount = max(count($salesData), 6);
                                        @endphp
                                        @if($salesData->count() > 0)
                                            @foreach($salesData as $i => $month)
                                                @php
                                                    $barH = ($month['total'] / $maxVal) * 180;
                                                    $barW = 600 / $barCount - 20;
                                                    $x = $i * (600 / $barCount) + 10;
                                                @endphp
                                                <rect x="{{ $x }}" y="{{ 200 - $barH }}" width="{{ max($barW, 10) }}" height="{{ $barH }}" rx="4" fill="url(#barGradient)" />
                                                <text x="{{ $x + $barW / 2 }}" y="195" text-anchor="middle" class="text-[10px]" fill="#9ca3af">{{ $month['month'] ?? '' }}</text>
                                            @endforeach
                                        @else
                                            @for($i = 0; $i < 6; $i++)
                                                @php
                                                    $barH = rand(40, 160);
                                                    $barW = 80;
                                                    $x = $i * 100 + 10;
                                                @endphp
                                                <rect x="{{ $x }}" y="{{ 200 - $barH }}" width="{{ $barW }}" height="{{ $barH }}" rx="4" fill="url(#barGradient)" />
                                                <text x="{{ $x + $barW / 2 }}" y="195" text-anchor="middle" class="text-[10px]" fill="#9ca3af">Month</text>
                                            @endfor
                                        @endif
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Status</h2>
                            </div>
                            <div class="p-5 space-y-3">
                                @php
                                    $stats = $orderStats ?? [];
                                    $total = collect($stats)->sum();
                                @endphp
                                @if($total > 0)
                                    @foreach(['pending' => ['label' => 'Pending', 'color' => 'bg-yellow-500'], 'processing' => ['label' => 'Processing', 'color' => 'bg-indigo-500'], 'shipped' => ['label' => 'Shipped', 'color' => 'bg-purple-500'], 'delivered' => ['label' => 'Delivered', 'color' => 'bg-green-500'], 'cancelled' => ['label' => 'Cancelled', 'color' => 'bg-red-500']] as $key => $info)
                                        @php $count = $stats[$key] ?? 0; @endphp
                                        <div>
                                            <div class="flex items-center justify-between text-sm mb-1">
                                                <span class="text-gray-600 dark:text-gray-400">{{ $info['label'] }}</span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $count }}</span>
                                            </div>
                                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full {{ $info['color'] }} transition-all" style="width: {{ $total > 0 ? ($count / $total) * 100 : 0 }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No order data yet</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Monthly Revenue</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                            <th class="px-5 py-3">Month</th>
                                            <th class="px-5 py-3 text-right">Orders</th>
                                            <th class="px-5 py-3 text-right">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @forelse($monthlySales ?? [] as $month)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                <td class="px-5 py-3 text-sm text-gray-900 dark:text-white font-medium">{{ $month['month'] ?? 'N/A' }}</td>
                                                <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300 text-right">{{ $month['count'] ?? 0 }}</td>
                                                <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($month['total'] ?? 0, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No revenue data yet</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Top Selling Products</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                            <th class="px-5 py-3">Product</th>
                                            <th class="px-5 py-3 text-right">Sold</th>
                                            <th class="px-5 py-3 text-right">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @forelse($topProducts ?? [] as $product)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                <td class="px-5 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                            @if($product->thumbnail)
                                                                <img src="{{ $product->thumbnail }}" alt="" class="w-full h-full object-cover">
                                                            @else
                                                                <div class="w-full h-full flex items-center justify-center">
                                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[180px]">{{ $product->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300 text-right">{{ $product->total_sold ?? 0 }}</td>
                                                <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format(($product->total_sold ?? 0) * ($product->price ?? 0), 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No sales data yet</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
