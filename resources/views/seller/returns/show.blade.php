<x-app-layout>
    @section('title', 'Return Request #' . $return->id)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Return Request #{{ $return->id }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Return details and management</p>
                        </div>
                        <a href="{{ route('seller.returns.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Returns
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Return Information</h2>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return ID</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">#{{ $return->id }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                                        <a href="#" class="text-market-600 dark:text-market-400 hover:underline">Order #{{ $return->order_id }}</a>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $return->product?->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</span>
                                    <p class="mt-1">
                                        @php
                                            $statusBadge = match($return->status) {
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                'refunded' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">{{ ucfirst($return->status) }}</span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $return->user?->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $return->user?->email ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $return->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Customer Notes</h2>
                        </div>
                        <div class="p-5">
                            @if($return->customer_notes)
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $return->customer_notes }}</p>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No customer notes provided</p>
                            @endif
                        </div>
                    </div>

                    @if($return->admin_notes)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Admin Notes</h2>
                            </div>
                            <div class="p-5">
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $return->admin_notes }}</p>
                            </div>
                        </div>
                    @endif

                    @if($return->status === 'pending')
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Take Action</h2>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <form method="POST" action="{{ route('seller.returns.update', $return) }}" class="space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <div>
                                            <label for="admin_notes_approve" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
                                            <textarea id="admin_notes_approve" name="admin_notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500" placeholder="Optional notes for the customer..."></textarea>
                                        </div>
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Approve Return
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('seller.returns.update', $return) }}" class="space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <div>
                                            <label for="admin_notes_reject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for Rejection</label>
                                            <textarea id="admin_notes_reject" name="admin_notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500" placeholder="Explain why the return was rejected..." required></textarea>
                                        </div>
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Reject Return
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
