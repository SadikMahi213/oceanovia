<x-app-layout>
    @section('title', 'Payout Details')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.payouts.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Payout Details</h1>
                                @php
                                    $badge = match($payout->status) {
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($payout->status) }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Requested on {{ $payout->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($payout->status === 'pending')
                                <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Approve Payout
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}" onsubmit="return confirm('Reject this payout?')" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject Payout
                                    </button>
                                </form>
                            @endif
                            @if($payout->status === 'approved')
                                <form method="POST" action="{{ route('admin.payouts.complete', $payout) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Complete Payout
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payout Information</h2>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Payout ID</span>
                                        <span class="text-sm font-mono text-gray-900 dark:text-white">#{{ $payout->id }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Payment Method</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $payout->payment_method ?? '—' }}</span>
                                    </div>
                                    @if($payout->payment_details)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Payment Details</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->payment_details }}</span>
                                        </div>
                                    @endif
                                    @if($payout->reference)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Reference</span>
                                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $payout->reference }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Requested</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $payout->created_at->format('M d, Y \a\t h:i A') }}</span>
                                    </div>
                                    @if($payout->approved_at)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Approved</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $payout->approved_at->format('M d, Y \a\t h:i A') }}</span>
                                        </div>
                                    @endif
                                    @if($payout->completed_at)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Completed</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $payout->completed_at->format('M d, Y \a\t h:i A') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($payout->commissions && $payout->commissions->isNotEmpty())
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Commission Items</h2>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                                    <th class="px-5 py-3">Order</th>
                                                    <th class="px-5 py-3">Product</th>
                                                    <th class="px-5 py-3 text-right">Amount</th>
                                                    <th class="px-5 py-3 text-right">Commission</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach($payout->commissions as $commission)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                        <td class="px-5 py-3">
                                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $commission->order?->order_number ?? '—' }}</span>
                                                        </td>
                                                        <td class="px-5 py-3">
                                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $commission->orderItem?->product?->name ?? '—' }}</span>
                                                        </td>
                                                        <td class="px-5 py-3 text-sm text-gray-900 dark:text-white text-right">${{ number_format($commission->subtotal ?? 0, 2) }}</td>
                                                        <td class="px-5 py-3 text-sm text-gray-900 dark:text-white text-right">${{ number_format($commission->amount ?? 0, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Seller</h2>
                                </div>
                                <div class="p-5 space-y-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->seller?->name ?? '—' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payout->seller?->email }}</p>
                                    @if($payout->seller?->shop_name)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payout->seller->shop_name }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Amount Summary</h2>
                                </div>
                                <div class="p-5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Amount</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($payout->amount, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Fee</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">${{ number_format($payout->fee ?? 0, 2) }}</span>
                                    </div>
                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Net Amount</span>
                                        <span class="text-lg font-bold text-green-600 dark:text-green-400">${{ number_format($payout->net ?? $payout->amount, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($payout->rejection_reason)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rejection Reason</h2>
                                    </div>
                                    <div class="p-5">
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $payout->rejection_reason }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
