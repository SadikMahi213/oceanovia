<x-app-layout>
    @section('title', 'Procurement Orders')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Procurement Orders</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Purchase orders with your suppliers for sourced products</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">PO Number</th>
                                        <th class="px-5 py-3">Supplier</th>
                                        <th class="px-5 py-3 text-right">Items</th>
                                        <th class="px-5 py-3 text-right">Subtotal</th>
                                        <th class="px-5 py-3">Tracking</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($procurements as $procurement)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('seller.procurement.show', $procurement) }}" class="text-sm font-medium text-market-700 dark:text-market-300 hover:underline">{{ $procurement->po_number }}</a>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $procurement->created_at?->format('M d, Y') }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="text-sm text-gray-900 dark:text-white">{{ $procurement->supplier?->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $procurement->supplier?->supplierProfile?->company_name ?? '—' }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-right text-sm text-gray-500 dark:text-gray-400">{{ $procurement->total_quantity }}</td>
                                            <td class="px-5 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($procurement->subtotal, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                @if($procurement->tracking_number)
                                                    <a href="{{ $procurement->tracking_url ?: '#' }}" target="_blank" rel="noopener" class="text-market-700 dark:text-market-300 hover:underline">{{ $procurement->tracking_number }}</a>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $procurement->status_color }}-100 text-{{ $procurement->status_color }}-800 dark:bg-{{ $procurement->status_color }}-900/30 dark:text-{{ $procurement->status_color }}-400">{{ $procurement->status_label }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <a href="{{ route('seller.procurement.show', $procurement) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No procurement orders yet</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Procurement orders appear automatically when customers buy products you source from suppliers.</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($procurements->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $procurements->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>