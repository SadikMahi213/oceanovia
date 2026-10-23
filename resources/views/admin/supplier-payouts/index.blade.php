<x-app-layout>
    @section('title', 'Supplier Payouts')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Supplier Payouts</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review and settle supplier payout requests</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $key => $label)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $label }}</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $summary[$key] ?? 0 }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Supplier</th>
                                        <th class="px-5 py-3 text-right">Amount</th>
                                        <th class="px-5 py-3 text-right">Fee</th>
                                        <th class="px-5 py-3 text-right">Net</th>
                                        <th class="px-5 py-3">Method</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($payouts as $payout)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->supplier?->name ?? '—' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $payout->supplier?->email }}</div>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">${{ number_format($payout->platform_fee ?? 0, 2) }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->net_amount ?? $payout->amount, 2) }}</td>
                                            <td class="px-5 py-4">
                                                <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $payout->payment_method ?? '—' }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $badge = match($payout->status) {
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($payout->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payout->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('admin.supplier-payouts.show', $payout) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No supplier payouts</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Payout requests from suppliers will appear here</p>
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
