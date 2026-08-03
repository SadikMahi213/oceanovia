<x-app-layout>
    @section('title', 'Invoice')
    @push('styles')
        <style>
            @media print {
                body * { visibility: hidden; }
                #invoice-area, #invoice-area * { visibility: visible; }
                #invoice-area { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none !important; }
            }
        </style>
    @endpush
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen no-print">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="$profile?->company_name" :companyLogo="$profile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6 no-print">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Order {{ $order->order_number }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('supplier.orders.show', $order) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back
                            </a>
                            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="invoice-area" class="max-w-4xl mx-auto bg-white p-8 sm:p-12 print:p-0" style="margin-top: -4rem;">
        <div class="border-b-2 border-market-500 pb-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    @if($profile?->logo_url)
                        <img src="{{ $profile->logo_url }}" alt="{{ $profile->company_name }}" class="h-16 w-auto mb-3">
                    @else
                        <div class="w-16 h-16 bg-gradient-to-br from-market-500 to-market-700 rounded-xl flex items-center justify-center mb-3">
                            <span class="text-white font-bold text-2xl">{{ substr($profile->company_name ?? 'C', 0, 1) }}</span>
                        </div>
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900">{{ $profile->company_name ?? 'Company Name' }}</h1>
                    <p class="text-sm text-gray-600">{{ $profile->address }}</p>
                    <p class="text-sm text-gray-600">{{ $profile->phone }}</p>
                    <p class="text-sm text-gray-600">{{ $profile->contact_email ?? $profile->user?->email }}</p>
                </div>
                <div class="text-right">
                    <h2 class="text-3xl font-bold text-market-600">INVOICE</h2>
                    <p class="text-sm text-gray-500 mt-2">Invoice #: INV-{{ $order->order_number }}</p>
                    <p class="text-sm text-gray-500">Order #: {{ $order->order_number }}</p>
                    <p class="text-sm text-gray-500">Date: {{ $order->created_at->format('F d, Y') }}</p>
                    <p class="text-sm text-gray-500">Customer: {{ $order->user?->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto mb-6">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Unit Price</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $item->sku ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">${{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No items</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-8">
            <div class="w-64 space-y-2">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span>${{ number_format($items->sum('subtotal'), 2) }}</span>
                </div>
                @if($order->shipping_cost > 0)
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <span>Shipping</span>
                        <span>${{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between text-lg font-bold text-gray-900 border-t border-gray-200 pt-2">
                    <span>Total</span>
                    <span>${{ number_format($order->total ?? $items->sum('subtotal'), 2) }}</span>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-6 text-center text-sm text-gray-500">
            <p>Thank you for your business!</p>
            <p class="mt-1">{{ $profile->company_name ?? 'Company Name' }} | {{ $profile->phone }} | {{ $profile->contact_email ?? $profile->user?->email }}</p>
        </div>
    </div>
</x-app-layout>
