<x-app-layout>
    @section('title', 'Reports')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reports</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Generate and download business reports</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sales Report</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">View detailed sales data and trends</p>
                            <a href="{{ route('supplier.reports.generate', 'sales') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Revenue Report</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">Track your earnings and revenue streams</p>
                            <a href="{{ route('supplier.reports.generate', 'revenue') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Inventory Report</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">Monitor stock levels and inventory health</p>
                            <a href="{{ route('supplier.reports.generate', 'inventory') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Order Report</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">Analyze order fulfillment and trends</p>
                            <a href="{{ route('supplier.reports.generate', 'orders') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Product Performance</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">See which products perform best</p>
                            <a href="{{ route('supplier.reports.generate', 'products') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Settlement Report</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">Review payout and settlement records</p>
                            <a href="{{ route('supplier.reports.generate', 'settlements') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tax Report</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">Tax summaries for filing purposes</p>
                            <a href="{{ route('supplier.reports.generate', 'tax') }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Generate →</a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Export Report</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Download a report in CSV format for a date range</p>
                        </div>
                        <form method="POST" action="{{ route('supplier.reports.export') }}" class="p-5 space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <x-input-label for="from_date" value="From Date" />
                                    <input type="date" id="from_date" name="from_date" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('from_date')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="to_date" value="To Date" />
                                    <input type="date" id="to_date" name="to_date" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('to_date')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="report_type" value="Report Type" />
                                    <select id="report_type" name="report_type" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="sales">Sales Report</option>
                                        <option value="revenue">Revenue Report</option>
                                        <option value="inventory">Inventory Report</option>
                                        <option value="orders">Orders Report</option>
                                        <option value="products">Product Performance</option>
                                        <option value="settlements">Settlement Report</option>
                                        <option value="tax">Tax Report</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('report_type')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="format" value="Format" />
                                    <select id="format" name="format" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="csv">CSV</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('format')" class="mt-1" />
                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Export Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>