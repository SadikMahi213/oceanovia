<x-app-layout>
    @section('title', $filter ? ucfirst($filter) . ' Orders' : 'Orders')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $filter ? ucfirst($filter) . ' Orders' : 'Orders' }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Orders containing your products</p>
                        </div>
                    </div>

                    <div class="mb-6 flex flex-wrap gap-2">
                        @php
                            $tabs = [
                                ['label' => 'All', 'route' => 'supplier.orders.index', 'params' => []],
                                ['label' => 'New', 'route' => 'supplier.orders.new', 'params' => []],
                                ['label' => 'Accepted', 'route' => 'supplier.orders.accepted', 'params' => []],
                                ['label' => 'Shipped', 'route' => 'supplier.orders.shipped', 'params' => []],
                                ['label' => 'Delivered', 'route' => 'supplier.orders.delivered', 'params' => []],
                                ['label' => 'Returned', 'route' => 'supplier.orders.returned', 'params' => []],
                                ['label' => 'Cancelled', 'route' => 'supplier.orders.cancelled', 'params' => []],
                            ];
                            $currentRoute = request()->route()?->getName();
                        @endphp
                        @foreach($tabs as $tab)
                            <a href="{{ route($tab['route'], $tab['params']) }}"
                               class="px-4 py-2 text-sm font-medium rounded-xl transition-colors {{ $currentRoute === $tab['route'] ? 'bg-market-50 dark:bg-market-900/30 text-market-700 dark:text-market-300' : 'text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                {{ $tab['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Order #</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3 text-right">Qty</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($orders as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('supplier.orders.show', $order) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">{{ $order->order_number }}</a>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $order->items->where('supplier_id', Auth::id())->pluck('product_name')->take(2)->join(', ') }}
                                                @if($order->items->where('supplier_id', Auth::id())->count() > 2)
                                                    <span class="text-xs">+{{ $order->items->where('supplier_id', Auth::id())->count() - 2 }} more</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">{{ $order->items->where('supplier_id', Auth::id())->sum('quantity') }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $colors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                        'shipped' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                        'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                    ];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors[$order->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $order->status_label }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('supplier.orders.show', $order) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                        View
                                                    </a>
                                                    @if($order->status === 'pending')
                                                        <form method="POST" action="{{ route('supplier.orders.accept', $order) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors">
                                                                Accept
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('supplier.orders.reject', $order) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                                Reject
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if(in_array($order->status, ['processing', 'confirmed']))
                                                        <form method="POST" action="{{ route('supplier.orders.fulfill', $order) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                                Fulfill
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No orders {{ $filter ? 'in ' . $filter . ' status' : 'yet' }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Orders containing your products will appear here</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
