<x-app-layout>
    @section('title', 'Refund Details')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.refunds.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Refund Details</h1>
                                @php
                                    $badge = match($refund->status) {
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($refund->status) }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Requested on {{ $refund->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        @if($refund->status === 'pending')
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Approve Refund
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('reject-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Reject Refund
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Refund Reason</h2>
                                </div>
                                <div class="p-5">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $refund->reason }}</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order Information</h2>
                                </div>
                                <div class="p-5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Order Number</span>
                                        <a href="{{ route('orders.show', $refund->order) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">{{ $refund->order?->order_number ?? '—' }}</a>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Order Total</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($refund->order?->total ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Order Status</span>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ match($refund->order?->status_color) { 'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400', 'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' } }}">{{ $refund->order?->status_label ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Order Date</span>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $refund->order?->created_at?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($refund->orderItem)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Refunded Item</h2>
                                    </div>
                                    <div class="p-5 flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0 flex items-center justify-center">
                                            @if($refund->orderItem->product?->thumbnail)
                                                <img src="{{ $refund->orderItem->product->thumbnail }}" alt="{{ $refund->orderItem->product_name }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $refund->orderItem->product_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $refund->orderItem->sku ?? '—' }} &middot; Qty: {{ $refund->orderItem->quantity }} &middot; ${{ number_format($refund->orderItem->unit_price, 2) }} ea</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Customer</h2>
                                </div>
                                <div class="p-5 space-y-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $refund->user?->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $refund->user?->email }}</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Refund Summary</h2>
                                </div>
                                <div class="p-5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Refund Amount</span>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($refund->amount, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Method</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $refund->refund_method ?? 'Original' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Status</span>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($refund->status) }}</span>
                                    </div>
                                    @if($refund->approved_at)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Approved At</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $refund->approved_at->format('M d, Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($refund->notes)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Admin Notes</h2>
                                    </div>
                                    <div class="p-5">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $refund->notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
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
                                    <x-input-label for="rejection_reason" value="Rejection Reason *" />
                                    <textarea id="rejection_reason" name="rejection_reason" rows="3" required
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
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
