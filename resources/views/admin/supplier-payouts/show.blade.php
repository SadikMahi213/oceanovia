<x-app-layout>
    @section('title', 'Supplier Payout Details')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.supplier-payouts.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Supplier Payout Details</h1>
                                @php
                                    $badge = match($payout->status) {
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ $payout->status_label }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Requested on {{ $payout->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($payout->status === \App\Models\SupplierPayout::STATUS_PENDING)
                                <form method="POST" action="{{ route('admin.supplier-payouts.approve', $payout) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Approve
                                    </button>
                                </form>
                            @endif
                            @if(in_array($payout->status, [\App\Models\SupplierPayout::STATUS_PENDING, \App\Models\SupplierPayout::STATUS_PROCESSING], true))
                                <form method="POST" action="{{ route('admin.supplier-payouts.reject', $payout) }}" onsubmit="return confirm('Reject this payout and return the funds to the supplier?')" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </form>
                            @endif
                            @if($payout->status === \App\Models\SupplierPayout::STATUS_PROCESSING)
                                <form method="POST" action="{{ route('admin.supplier-payouts.complete', $payout) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Mark Completed
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
                                    <div class="flex items-start justify-between gap-6">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Account Details</span>
                                        <span class="text-sm font-mono text-gray-900 dark:text-white text-right break-all">{{ json_encode($payout->account_details) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Requested</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $payout->created_at->format('M d, Y \a\t h:i A') }}</span>
                                    </div>
                                    @if($payout->processed_at)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Processed</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $payout->processed_at->format('M d, Y \a\t h:i A') }}</span>
                                        </div>
                                    @endif
                                    @if($payout->processor)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Processed By</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $payout->processor->name }}</span>
                                        </div>
                                    @endif
                                    @if($payout->admin_notes)
                                        <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Admin Notes</p>
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $payout->admin_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Supplier</h2>
                                </div>
                                <div class="p-5 space-y-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $payout->supplier?->name ?? '—' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payout->supplier?->email }}</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Amount Summary</h2>
                                </div>
                                <div class="p-5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Gross Amount</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($payout->amount, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Platform Fee</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">${{ number_format($payout->platform_fee ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Tax</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">${{ number_format($payout->tax ?? 0, 2) }}</span>
                                    </div>
                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Net Amount</span>
                                        <span class="text-lg font-bold text-green-600 dark:text-green-400">${{ number_format($payout->net_amount ?? $payout->amount, 2) }}</span>
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
