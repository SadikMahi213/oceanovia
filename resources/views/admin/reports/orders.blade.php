<x-app-layout>
    @section('title', 'Order Analytics')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order Analytics</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Breakdown of orders by status and payment method</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 border-yellow-200 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-400',
                                'confirmed' => 'bg-blue-100 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-400',
                                'processing' => 'bg-indigo-100 border-indigo-200 text-indigo-800 dark:bg-indigo-900/30 dark:border-indigo-700 dark:text-indigo-400',
                                'shipped' => 'bg-purple-100 border-purple-200 text-purple-800 dark:bg-purple-900/30 dark:border-purple-700 dark:text-purple-400',
                                'delivered' => 'bg-green-100 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-700 dark:text-green-400',
                                'cancelled' => 'bg-red-100 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-700 dark:text-red-400',
                                'refunded' => 'bg-gray-100 border-gray-200 text-gray-800 dark:bg-gray-900/30 dark:border-gray-700 dark:text-gray-400',
                            ];
                        @endphp
                        @foreach($statusBreakdown as $status)
                            <div class="rounded-2xl border shadow-sm p-4 {{ $statusColors[$status->status] ?? 'bg-gray-100 border-gray-200 text-gray-800' }}">
                                <p class="text-xs font-semibold uppercase tracking-wider">{{ ucfirst($status->status) }}</p>
                                <p class="text-2xl font-bold mt-1">{{ $status->count }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Methods</h2>
                            @if($paymentMethods->count())
                                @php $maxPayment = $paymentMethods->max('count') ?: 1; @endphp
                                <div class="space-y-3">
                                    @foreach($paymentMethods as $pm)
                                        <div>
                                            <div class="flex justify-between text-sm mb-1">
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($pm->payment_method) }}</span>
                                                <span class="text-gray-500 dark:text-gray-400">{{ $pm->count }} (${{ number_format($pm->total, 2) }})</span>
                                            </div>
                                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5">
                                                <div class="bg-market-500 h-2.5 rounded-full transition-all" style="width: {{ ($pm->count / $maxPayment) * 100 }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No payment data available.</p>
                            @endif
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Orders (Last 30 Days)</h2>
                            @if($dailyOrders->count())
                                @php
                                    $maxOrders = $dailyOrders->max('count') ?: 1;
                                    $chartHeight = 180;
                                @endphp
                                <div class="relative" style="height: {{ $chartHeight + 40 }}px;">
                                    <svg viewBox="0 0 {{ max($dailyOrders->count() * 40, 200) }} {{ $chartHeight + 40 }}" class="w-full h-full" preserveAspectRatio="none">
                                        @foreach($dailyOrders as $i => $day)
                                            @php
                                                $barHeight = ($day->count / $maxOrders) * $chartHeight;
                                                $x = $i * 40 + 10;
                                                $y = $chartHeight - $barHeight;
                                            @endphp
                                            <rect x="{{ $x }}" y="{{ $y }}" width="20" height="{{ $barHeight }}" fill="currentColor" class="text-market-500 hover:opacity-80 transition-opacity" rx="3">
                                                <title>{{ $day->count }} orders - {{ $day->date }}</title>
                                            </rect>
                                        @endforeach
                                    </svg>
                                    <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400 overflow-x-auto">
                                        @foreach($dailyOrders as $day)
                                            <span class="shrink-0 text-center" style="width:40px">{{ \Carbon\Carbon::parse($day->date)->format('M d') }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No order data available.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
