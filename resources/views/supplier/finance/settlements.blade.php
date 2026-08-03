<x-app-layout>
    @section('title', 'Settlements')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settlements</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your payouts and platform fees</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Paid</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($balance?->total_withdrawn ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pending Settlements</p>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">${{ number_format($balance?->pending_balance ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Platform Fees</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($balance?->platform_fees ?? 0, 2) }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payout History</h2>
                            <form method="GET" action="{{ route('supplier.settlements.index') }}" class="flex items-center gap-2">
                                <select name="status" onchange="this.form.submit()"
                                    class="text-sm rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-market-500 focus:ring-market-500">
                                    <option value="">All Statuses</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Amount</th>
                                        <th class="px-5 py-3 text-right">Platform Fee</th>
                                        <th class="px-5 py-3 text-right">Tax</th>
                                        <th class="px-5 py-3 text-right">Net Amount</th>
                                        <th class="px-5 py-3">Method</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Admin Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($payouts as $payout)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payout->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">${{ number_format($payout->platform_fee ?? 0, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">${{ number_format($payout->tax ?? 0, 2) }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->net_amount ?? $payout->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $payout->payment_method }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    ];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$payout->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($payout->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[150px] truncate">{{ $payout->admin_notes ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No settlements yet</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Payout history will appear here</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($payouts->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $payouts->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>