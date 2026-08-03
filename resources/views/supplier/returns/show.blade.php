<x-app-layout>
    @section('title', 'Return Request #' . $return->id)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('supplier.returns.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Return Request #{{ $return->id }}</h1>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'refunded' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'replaced' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                    ];
                                @endphp
                                <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$return->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($return->status) }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submitted on {{ $return->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Return Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return ID</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">#{{ $return->id }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                                            <a href="{{ route('supplier.orders.show', $return->order) }}" class="text-market-600 dark:text-market-400 hover:underline">{{ $return->order?->order_number ?? 'N/A' }}</a>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</p>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $return->orderItem?->product_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</p>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ $return->user?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</p>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ ucfirst($return->type ?? 'Return') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</p>
                                        <p class="mt-1">
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$return->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($return->status) }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Reason</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3">{{ $return->reason ?? 'No reason provided' }}</p>
                                </div>
                                @if($return->customer_explanation)
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Customer Notes</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3">{{ $return->customer_explanation }}</p>
                                    </div>
                                @endif
                                @if($return->seller_note)
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Admin Notes</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3">{{ $return->seller_note }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($return->status === 'pending')
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Process Return</h2>
                                </div>
                                <div class="p-5">
                                    <form method="POST" action="{{ route('supplier.returns.update', $return) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="space-y-4">
                                            <div>
                                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Action</label>
                                                <select id="status" name="status" required
                                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                    <option value="refunded">Refund</option>
                                                    <option value="replaced">Replace</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="admin_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Admin Notes</label>
                                                <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Add notes about this decision..."
                                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"></textarea>
                                            </div>
                                            <div class="flex justify-end">
                                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                                    Update Return
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
