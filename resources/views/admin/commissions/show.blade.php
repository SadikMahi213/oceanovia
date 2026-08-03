<x-app-layout>
    @section('title', 'Commission #'.$commission->id)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <a href="{{ route('admin.commissions.index') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline inline-flex items-center gap-1 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back to Commissions
                            </a>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Commission</h1>
                        </div>
                        @php
                            $statusBadge = $commission->status === 'paid'
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $statusBadge }}">{{ ucfirst($commission->status) }}</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Information</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Order #:</span> {{ $commission->order?->order_number ?? '—' }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Order Total:</span> ${{ number_format($commission->order?->total ?? 0, 2) }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Order Date:</span> {{ $commission->order?->created_at?->format('M d, Y g:i A') ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Item Information</h3>
                                @if($commission->orderItem)
                                    <div class="space-y-2 text-sm">
                                        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Product:</span> {{ $commission->orderItem->product?->name ?? $commission->orderItem->product_name ?? '—' }}</p>
                                        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Quantity:</span> {{ $commission->orderItem->quantity ?? '—' }}</p>
                                        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Price:</span> ${{ number_format($commission->orderItem->price ?? 0, 2) }}</p>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Item details not available</p>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Seller Details</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Name:</span> {{ $commission->seller?->name ?? '—' }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $commission->seller?->email ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Commission Details</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Rate:</span> {{ number_format($commission->rate * 100, 2) }}%</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Subtotal:</span> ${{ number_format($commission->subtotal, 2) }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Amount:</span> ${{ number_format($commission->amount, 2) }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Status:</span>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">{{ ucfirst($commission->status) }}</span>
                                    </p>
                                    @if($commission->paid_at)
                                        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Paid At:</span> {{ $commission->paid_at->format('M d, Y g:i A') }}</p>
                                    @endif
                                    @if($commission->payout)
                                        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Payout:</span> <a href="{{ route('admin.payouts.show', $commission->payout) }}" class="text-market-600 dark:text-market-400 hover:underline">#{{ $commission->payout->id }}</a></p>
                                    @endif
                                </div>
                            </div>

                            @if($commission->status === 'pending')
                                <form method="POST" action="{{ route('admin.commissions.mark-paid', $commission) }}" onsubmit="return confirm('Mark this commission as paid?')">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Mark as Paid
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
