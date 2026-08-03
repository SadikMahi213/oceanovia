<x-app-layout>
    @section('title', 'Payouts')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Payouts</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage seller payouts</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Seller</th>
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
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->seller?->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $payout->seller?->email }}</div>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">${{ number_format($payout->fee ?? 0, 2) }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->net ?? $payout->amount, 2) }}</td>
                                            <td class="px-5 py-4">
                                                <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $payout->payment_method ?? '—' }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $badge = match($payout->status) {
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'approved' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($payout->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payout->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    @if($payout->status === 'pending')
                                                        <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}" onsubmit="return confirm('Reject this payout?')" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                                Reject
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($payout->status === 'approved')
                                                        <form method="POST" action="{{ route('admin.payouts.complete', $payout) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                                                                Complete
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No payouts</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Payout requests from sellers will appear here</p>
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
