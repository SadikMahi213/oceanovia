<x-app-layout>
    @section('title', 'Reports')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reports</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Analyze your store performance</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <a href="#" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 hover:shadow-md hover:border-market-200 dark:hover:border-market-700 transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-market-50 dark:bg-market-900/30 flex items-center justify-center shrink-0 group-hover:bg-market-100 dark:group-hover:bg-market-900/50 transition-colors">
                                    <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-market-600 dark:group-hover:text-market-400 transition-colors">Sales Report</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View sales data over time</p>
                                </div>
                            </div>
                        </a>

                        <a href="#" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 hover:shadow-md hover:border-market-200 dark:hover:border-market-700 transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0 group-hover:bg-green-100 dark:group-hover:bg-green-900/50 transition-colors">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">Product Performance</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Top and bottom selling products</p>
                                </div>
                            </div>
                        </a>

                        <a href="#" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 hover:shadow-md hover:border-market-200 dark:hover:border-market-700 transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 transition-colors">
                                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Order Summary</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Order statistics by status</p>
                                </div>
                            </div>
                        </a>

                        <a href="#" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 hover:shadow-md hover:border-market-200 dark:hover:border-market-700 transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center shrink-0 group-hover:bg-yellow-100 dark:group-hover:bg-yellow-900/50 transition-colors">
                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition-colors">Payout History</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Payout records and details</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Export Data</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Download your store data for offline analysis</p>
                        </div>
                        <div class="p-5">
                            <form method="POST" action="{{ route('seller.reports.export') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                                @csrf
                                <div>
                                    <x-input-label for="from" value="From Date" />
                                    <input type="date" id="from" name="from"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <x-input-label for="to" value="To Date" />
                                    <input type="date" id="to" name="to"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <x-input-label for="report_type" value="Report Type" />
                                    <select id="report_type" name="report_type"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="sales">Sales Report</option>
                                        <option value="products">Product Performance</option>
                                        <option value="orders">Order Summary</option>
                                        <option value="payouts">Payout History</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="format" value="Format" />
                                    <select id="format" name="format"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="csv">CSV</option>
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-4 flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Export
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
