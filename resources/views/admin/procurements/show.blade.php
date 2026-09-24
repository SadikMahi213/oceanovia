<x-app-layout>
    @section('title', 'Procurement '.$procurement->po_number)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <a href="{{ route('admin.procurements.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-market-600 transition-colors">← Back to Procurements</a>
                        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $procurement->po_number }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Created {{ $procurement->created_at?->format('M d, Y h:i A') }}</p>
                            </div>
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-{{ $procurement->status_color }}-100 text-{{ $procurement->status_color }}-800 dark:bg-{{ $procurement->status_color }}-900/30 dark:text-{{ $procurement->status_color }}-400">{{ $procurement->status_label }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items</h2>
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
                                                    <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</td>
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
                                            <dd class="text-gray-900 dark:text-white mt-0.5">{{ $procurement->tracking_number }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs text-gray-500 dark:text-gray-400">Shipped At</dt>
                                            <dd class="text-gray-900 dark:text-white mt-0.5">{{ $procurement->shipped_at?->format('M d, Y h:i A') ?? '—' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Details</h2>
                                <dl class="space-y-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Order</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $procurement->order?->order_number ?? '—' }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Customer</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $procurement->order?->user?->name ?? '—' }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Total Quantity</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $procurement->total_quantity }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($procurement->subtotal, 2) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Settled</dt>
                                        <dd class="font-medium {{ $procurement->is_settled ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">{{ $procurement->is_settled ? 'Yes' : 'No' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Parties</h2>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $procurement->supplier?->name ?? '—' }} ({{ $procurement->supplier?->email ?? '—' }})</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $procurement->supplier?->supplierProfile?->company_name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Seller</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">{{ $procurement->seller?->name ?? '—' }} ({{ $procurement->seller?->email ?? '—' }})</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $procurement->seller?->sellerProfile?->store_name ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>