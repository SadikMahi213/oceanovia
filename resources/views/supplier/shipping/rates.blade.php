<x-app-layout>
    @section('title', 'Shipping Rates - ' . $zone->name)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('supplier.shipping.zones') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shipping Rates</h1>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Zone: {{ $zone->name }}</p>
                        </div>
                        <button onclick="document.getElementById('addRateForm').classList.toggle('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Rate
                        </button>
                    </div>

                    <div id="addRateForm" class="hidden mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">New Shipping Rate</h3>
                        <form method="POST" action="{{ route('supplier.shipping.rates.store', $zone) }}">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rate Name</label>
                                    <input type="text" name="name" required placeholder="e.g. Standard Shipping"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Carrier</label>
                                    <input type="text" name="carrier" placeholder="e.g. USPS, FedEx, UPS"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                    <select name="type"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="flat">Flat</option>
                                        <option value="weight_based">Weight Based</option>
                                        <option value="order_total_based">Order Total Based</option>
                                        <option value="free">Free</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rate ($)</label>
                                    <input type="number" step="0.01" min="0" name="rate" required placeholder="0.00"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Weight</label>
                                    <input type="number" step="0.01" min="0" name="min_weight" placeholder="0"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Weight</label>
                                    <input type="number" step="0.01" min="0" name="max_weight" placeholder="0"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Est. Delivery (days)</label>
                                    <div class="flex items-center gap-2 mt-1">
                                        <input type="number" min="0" name="estimated_days_min" placeholder="Min"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <span class="text-gray-500">-</span>
                                        <input type="number" min="0" name="estimated_days_max" placeholder="Max"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Order Total ($)</label>
                                    <input type="number" step="0.01" min="0" name="min_order_total" placeholder="0.00"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Order Total ($)</label>
                                    <input type="number" step="0.01" min="0" name="max_order_total" placeholder="0.00"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mb-4">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" checked
                                        class="rounded border-gray-300 dark:border-gray-600 text-market-600 focus:ring-market-500">
                                    Active
                                </label>
                            </div>
                            <div class="flex justify-end gap-3">
                                <button type="button" onclick="document.getElementById('addRateForm').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium bg-market-600 hover:bg-market-700 text-white rounded-xl transition-colors">
                                    Save Rate
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Name</th>
                                        <th class="px-5 py-3">Carrier</th>
                                        <th class="px-5 py-3">Type</th>
                                        <th class="px-5 py-3 text-right">Rate</th>
                                        <th class="px-5 py-3 text-right">Min / Max Weight</th>
                                        <th class="px-5 py-3 text-right">Min / Max Total</th>
                                        <th class="px-5 py-3 text-right">Est. Delivery</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($rates as $rate)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" id="rate-row-{{ $rate->id }}">
                                            <td class="px-5 py-4">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rate->name }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $rate->carrier ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $rate->type)) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white text-right">${{ number_format($rate->rate, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">
                                                @if($rate->min_weight || $rate->max_weight)
                                                    {{ $rate->min_weight ?: 0 }} - {{ $rate->max_weight ?: '∞' }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">
                                                @if($rate->min_order_total || $rate->max_order_total)
                                                    ${{ number_format($rate->min_order_total ?: 0, 2) }} - ${{ number_format($rate->max_order_total ?: 999999, 2) }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">
                                                @if($rate->estimated_days_min || $rate->estimated_days_max)
                                                    {{ $rate->estimated_days_min ?: 1 }} - {{ $rate->estimated_days_max ?: 1 }} days
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rate->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $rate->is_active ? 'Active' : 'Inactive' }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button onclick="editRate({{ $rate->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="{{ route('supplier.shipping.rates.destroy', $rate) }}" onsubmit="return confirm('Delete this rate?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr id="edit-rate-form-{{ $rate->id }}" class="hidden">
                                            <td colspan="9" class="px-5 py-4 bg-gray-50 dark:bg-gray-800/50">
                                                <form method="POST" action="{{ route('supplier.shipping.rates.update', $rate) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Name</label>
                                                            <input type="text" name="name" value="{{ $rate->name }}" required
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Carrier</label>
                                                            <input type="text" name="carrier" value="{{ $rate->carrier }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Type</label>
                                                            <select name="type"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                                <option value="flat" {{ $rate->type === 'flat' ? 'selected' : '' }}>Flat</option>
                                                                <option value="weight_based" {{ $rate->type === 'weight_based' ? 'selected' : '' }}>Weight Based</option>
                                                                <option value="order_total_based" {{ $rate->type === 'order_total_based' ? 'selected' : '' }}>Order Total Based</option>
                                                                <option value="free" {{ $rate->type === 'free' ? 'selected' : '' }}>Free</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Rate ($)</label>
                                                            <input type="number" step="0.01" min="0" name="rate" value="{{ $rate->rate }}" required
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Min Weight</label>
                                                            <input type="number" step="0.01" min="0" name="min_weight" value="{{ $rate->min_weight }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Max Weight</label>
                                                            <input type="number" step="0.01" min="0" name="max_weight" value="{{ $rate->max_weight }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Min Order Total ($)</label>
                                                            <input type="number" step="0.01" min="0" name="min_order_total" value="{{ $rate->min_order_total }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Max Order Total ($)</label>
                                                            <input type="number" step="0.01" min="0" name="max_order_total" value="{{ $rate->max_order_total }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Est. Delivery (min days)</label>
                                                            <input type="number" min="0" name="estimated_days_min" value="{{ $rate->estimated_days_min }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Est. Delivery (max days)</label>
                                                            <input type="number" min="0" name="estimated_days_max" value="{{ $rate->estimated_days_max }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3 mb-4">
                                                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                            <input type="hidden" name="is_active" value="0">
                                                            <input type="checkbox" name="is_active" value="1" {{ $rate->is_active ? 'checked' : '' }}
                                                                class="rounded border-gray-300 dark:border-gray-600 text-market-600 focus:ring-market-500">
                                                            Active
                                                        </label>
                                                    </div>
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" onclick="cancelEditRate({{ $rate->id }})" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="px-4 py-2 text-sm font-medium bg-market-600 hover:bg-market-700 text-white rounded-xl transition-colors">
                                                            Update Rate
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No shipping rates</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add a rate for this zone to configure shipping costs</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            function editRate(id) {
                document.getElementById('rate-row-' + id).classList.add('hidden');
                document.getElementById('edit-rate-form-' + id).classList.remove('hidden');
            }
            function cancelEditRate(id) {
                document.getElementById('rate-row-' + id).classList.remove('hidden');
                document.getElementById('edit-rate-form-' + id).classList.add('hidden');
            }
        </script>
    @endpush
</x-app-layout>
