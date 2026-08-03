<x-app-layout>
    @section('title', 'Sales Report')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Report</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Overview of sales performance</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.reports.sales') }}" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 mb-6">
                        <div class="flex items-end gap-4 flex-wrap">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                                <input type="date" name="start_date" value="{{ $start->format('Y-m-d') }}" class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                                <input type="date" name="end_date" value="{{ $end->format('Y-m-d') }}" class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            </div>
                            <button type="submit" class="px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Filter</button>
                        </div>
                    </form>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($stats->total_revenue, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Orders</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats->total_orders }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Order Value</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($stats->avg_order_value, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Commission Earned</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($stats->total_commission, 2) }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Sales</h2>
                        @if($dailySales->count())
                            @php
                                $maxRevenue = $dailySales->max('revenue') ?: 1;
                                $chartHeight = 200;
                            @endphp
                            <div class="relative" style="height: {{ $chartHeight + 40 }}px;">
                                <svg viewBox="0 0 {{ max($dailySales->count() * 40, 200) }} {{ $chartHeight + 40 }}" class="w-full h-full" preserveAspectRatio="none">
                                    @foreach($dailySales as $i => $day)
                                        @php
                                            $barHeight = ($day->revenue / $maxRevenue) * $chartHeight;
                                            $x = $i * 40 + 10;
                                            $y = $chartHeight - $barHeight;
                                        @endphp
                                        <rect x="{{ $x }}" y="{{ $y }}" width="20" height="{{ $barHeight }}" fill="currentColor" class="text-market-500 hover:opacity-80 transition-opacity" rx="3">
                                            <title>${{ number_format($day->revenue, 0) }} - {{ $day->date }}</title>
                                        </rect>
                                    @endforeach
                                </svg>
                                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400 overflow-x-auto">
                                    @foreach($dailySales as $day)
                                        <span class="shrink-0 text-center" style="width:40px">{{ \Carbon\Carbon::parse($day->date)->format('M d') }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No sales data for the selected period.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
