<x-app-layout>
    @section('title', 'Order ' . $order->order_number)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('supplier.orders.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order {{ $order->order_number }}</h1>
                                <span class="inline-flex px-3 py-0.5 rounded-full text-xs font-medium {{ match($order->status_color) { 'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400', 'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' } }}">{{ $order->status_label }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Placed on {{ $order->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('supplier.orders.invoice', $order) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Invoice
                            </a>
                            @if($order->status === 'pending')
                                <form method="POST" action="{{ route('supplier.orders.accept', $order) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        Accept
                                    </button>
                                </form>
                            @endif
                            @if($order->status === 'processing')
                                <form method="POST" action="{{ route('supplier.orders.packed', $order) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        Mark Packed
                                    </button>
                                </form>
                            @endif
                            @if($order->status === 'packed')
                                <form method="POST" action="{{ route('supplier.orders.ready', $order) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        Ready for Pickup
                                    </button>
                                </form>
                            @endif
                            @if(in_array($order->status, ['processing', 'confirmed']))
                                <form method="POST" action="{{ route('supplier.orders.fulfill', $order) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Fulfill / Ship
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @php
                        $steps = ['pending', 'processing', 'packed', 'ready_for_pickup', 'shipped', 'delivered'];
                        $currentIndex = array_search($order->status, $steps);
                        if ($currentIndex === false) $currentIndex = -1;
                    @endphp
                    <div class="mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            @foreach($steps as $i => $step)
                                <div class="flex items-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $i <= $currentIndex ? 'bg-market-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500' }}">
                                            @if($i < $currentIndex)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                {{ $i + 1 }}
                                            @endif
                                        </div>
                                        <span class="mt-1 text-xs font-medium {{ $i <= $currentIndex ? 'text-market-700 dark:text-market-300' : 'text-gray-400 dark:text-gray-500' }}">{{ ucfirst(str_replace('_', ' ', $step)) }}</span>
                                    </div>
                                    @if(!$loop->last)
                                        <div class="w-12 sm:w-20 h-0.5 mx-2 {{ $i < $currentIndex ? 'bg-market-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Items in This Order</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">SKU</th>
                                        <th class="px-5 py-3 text-right">Qty</th>
                                        <th class="px-5 py-3 text-right">Price</th>
                                        <th class="px-5 py-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($order->items->where('supplier_id', Auth::id()) as $item)
                                        <tr class="bg-market-50/30 dark:bg-market-900/10 hover:bg-market-50/50 dark:hover:bg-market-900/20 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($item->product?->thumbnail)
                                                            <img src="{{ $item->product->thumbnail }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->sku ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $item->quantity }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">${{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No items from your supply in this order</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($order->items->where('supplier_id', Auth::id())->count() > 0)
                                    <tfoot>
                                        <tr class="bg-gray-50 dark:bg-gray-800/50">
                                            <td colspan="4" class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">Your Subtotal</td>
                                            <td class="px-5 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">${{ number_format($order->items->where('supplier_id', Auth::id())->sum('subtotal'), 2) }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($order->items->where('supplier_id', '!=', Auth::id())->count() > 0)
                        <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Other Items in This Order</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                            <th class="px-5 py-3">Product</th>
                                            <th class="px-5 py-3 text-right">Qty</th>
                                            <th class="px-5 py-3 text-right">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach($order->items->where('supplier_id', '!=', Auth::id()) as $item)
                                            <tr>
                                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->product_name }}</td>
                                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $item->quantity }}</td>
                                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">${{ number_format($item->unit_price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Internal Notes</h2>
                        </div>
                        <div class="p-5">
                            @php
                                $notes = is_string($order->admin_notes) ? json_decode($order->admin_notes, true) : ($order->admin_notes ?? []);
                            @endphp
                            @if(!empty($notes) && is_array($notes))
                                <div class="space-y-3 mb-4">
                                    @foreach($notes as $note)
                                        <div class="p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-100 dark:border-gray-700">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ is_array($note) ? ($note['message'] ?? $note['text'] ?? '') : $note }}</p>
                                            @if(is_array($note) && isset($note['created_at']))
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ \Carbon\Carbon::parse($note['created_at'])->format('M d, Y g:i A') }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <form method="POST" action="{{ route('supplier.orders.notes', $order) }}">
                                @csrf
                                <textarea name="note" rows="3" placeholder="Add an internal note..." class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"></textarea>
                                <div class="mt-3 flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        Add Note
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
