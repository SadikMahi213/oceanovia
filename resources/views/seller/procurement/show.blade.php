<x-app-layout>
    @section('title', 'Procurement '.$procurement->po_number)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <a href="{{ route('seller.procurement.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-market-600 transition-colors">← Back to Procurement Orders</a>
                        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $procurement->po_number }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Created {{ $procurement->created_at?->format('M d, Y h:i A') }} · Supplier: {{ $procurement->supplier?->name ?? '—' }}</p>
                            </div>
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-{{ $procurement->status_color }}-100 text-{{ $procurement->status_color }}-800 dark:bg-{{ $procurement->status_color }}-900/30 dark:text-{{ $procurement->status_color }}-400">{{ $procurement->status_label }}</span>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Items --}}
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items</h2>
                                    <a href="{{ $procurement->order ? route('seller.orders.show', $procurement->order) : '#' }}" class="text-xs font-medium text-market-700 dark:text-market-300 hover:underline">View customer order →</a>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                                <th class="px-5 py-3">Product</th>
                                                <th class="px-5 py-3">SKU</th>
                                                <th class="px-5 py-3 text-right">Qty</th>
                                                <th class="px-5 py-3 text-right">Unit Cost</th>
                                                <th class="px-5 py-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @forelse($procurement->items as $item)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td class="px-5 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                                @if($item->orderItem?->product?->thumbnail)
                                                                    <img src="{{ $item->orderItem->product->thumbnail }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                                                @elseif($item->supplierProduct?->thumbnail)
                                                                    <img src="{{ asset('storage/'.$item->supplierProduct->images[0]) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                                                @endif
                                                            </div>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[220px]">{{ $item->product_name }}</p>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->sku }}</td>
                                                    <td class="px-5 py-4 text-right text-sm text-gray-500 dark:text-gray-400">{{ $item->quantity }}</td>
                                                    <td class="px-5 py-4 text-right text-sm text-gray-500 dark:text-gray-400">${{ number_format($item->unit_cost, 2) }}</td>
                                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->subtotal, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No items</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                                <td colspan="3" class="px-5 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Total</td>
                                                <td class="px-5 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">${{ number_format($procurement->subtotal, 2) }}</td>
                                                <td class="px-5 py-3"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            @if($procurement->status === 'shipped' && $procurement->order)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Confirm Delivery</h2>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Confirm the supplier delivered this shipment to your customer.</p>
                                    <form method="POST" action="{{ route('seller.orders.update-status', $procurement->order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="delivered">
                                        <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-market-600 hover:bg-market-700 rounded-xl transition-colors">Mark as Delivered</button>
                                    </form>
                                </div>
                            @endif

                            @if($procurement->tracking_number)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Tracking</h2>
                                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Carrier</dt>
                                            <dd class="text-gray-900 dark:text-white mt-0.5">{{ $procurement->carrier ?? '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Tracking Number</dt>
                                            <dd class="text-gray-900 dark:text-white mt-0.5">
                                                @if($procurement->tracking_url)
                                                    <a href="{{ $procurement->tracking_url }}" target="_blank" rel="noopener" class="text-market-700 dark:text-market-300 hover:underline">{{ $procurement->tracking_number }}</a>
                                                @else
                                                    {{ $procurement->tracking_number }}
                                                @endif
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Shipped At</dt>
                                            <dd class="text-gray-900 dark:text-white mt-0.5">{{ $procurement->shipped_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            @endif
                        </div>

                        {{-- Sidebar --}}
                        <div class="space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Order Information</h2>
                                <dl class="space-y-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Order #</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $procurement->order?->order_number ?? '—' }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Total Quantity</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $procurement->total_quantity }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($procurement->subtotal, 2) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Supplier</h2>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $procurement->supplier?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $procurement->supplier?->supplierProfile?->company_name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>