<x-app-layout>
    @section('title', 'Refunds')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Refunds</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage customer refund requests</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Order</th>
                                        <th class="px-5 py-3">Customer</th>
                                        <th class="px-5 py-3 text-right">Amount</th>
                                        <th class="px-5 py-3">Reason</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($refunds as $refund)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('admin.refunds.show', $refund) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">{{ $refund->order?->order_number ?? '—' }}</a>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $refund->user?->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $refund->user?->email }}</div>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($refund->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[200px]">
                                                <span class="truncate block">{{ Str::limit($refund->reason, 60) }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $badge = match($refund->status) {
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($refund->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $refund->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('admin.refunds.show', $refund) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                        View
                                                    </a>
                                                    @if($refund->status === 'pending')
                                                        <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <button type="button" onclick="document.getElementById('reject-modal-{{ $refund->id }}').classList.remove('hidden')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                            Reject
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No refunds</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Refund requests from customers will appear here</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($refunds->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $refunds->links() }}
                            </div>
                        @endif
                    </div>

                    @foreach($refunds as $refund)
                        <div id="reject-modal-{{ $refund->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xl max-w-lg w-full mx-4" onclick="event.stopPropagation()">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reject Refund</h3>
                                    <button type="button" onclick="this.closest('.fixed').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="p-5 space-y-4">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <x-input-label for="reject_reason_{{ $refund->id }}" value="Rejection Reason *" />
                                        <textarea id="reject_reason_{{ $refund->id }}" name="rejection_reason" rows="3" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"></textarea>
                                    </div>
                                    <div class="flex items-center justify-end gap-3 pt-2">
                                        <button type="button" onclick="this.closest('.fixed').classList.add('hidden')" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                            Reject Refund
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
