<x-app-layout>
    @section('title', 'Return Requests')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Return Requests</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage customer return requests</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Return ID</th>
                                        <th class="px-5 py-3">Order</th>
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">Customer</th>
                                        <th class="px-5 py-3">Reason</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($returns as $return)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white">#{{ $return->id }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">#{{ $return->order_id }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $return->product?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $return->user?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[150px] truncate">{{ $return->reason ?? '—' }}</td>
                                            <td class="px-5 py-4">
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
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $return->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('seller.returns.show', $return) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No return requests yet</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Customer return requests will appear here</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($returns, 'hasPages') && $returns->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $returns->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
