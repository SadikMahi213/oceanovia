<x-app-layout>
    @section('title', 'Shipping Zones')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shipping Zones</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your shipping zones and regions</p>
                        </div>
                        <button onclick="document.getElementById('addZoneForm').classList.toggle('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Zone
                        </button>
                    </div>

                    <div id="addZoneForm" class="hidden mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">New Shipping Zone</h3>
                        <form method="POST" action="{{ route('supplier.shipping.zones.store') }}">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="zone_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zone Name</label>
                                    <input type="text" id="zone_name" name="name" required placeholder="e.g. Domestic, North America"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                                <div>
                                    <label for="zone_countries" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Countries (comma-separated)</label>
                                    <input type="text" id="zone_countries" name="countries" placeholder="e.g. US, CA, MX"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="is_active" value="1" checked
                                        class="rounded border-gray-300 dark:border-gray-600 text-market-600 focus:ring-market-500">
                                    Active
                                </label>
                            </div>
                            <div class="mt-4 flex justify-end gap-3">
                                <button type="button" onclick="document.getElementById('addZoneForm').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium bg-market-600 hover:bg-market-700 text-white rounded-xl transition-colors">
                                    Save Zone
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @forelse($zones as $zone)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5" id="zone-card-{{ $zone->id }}">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $zone->name }}</h3>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <form method="POST" action="{{ route('supplier.shipping.zones.update', $zone) }}" id="zone-toggle-{{ $zone->id }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" {{ $zone->is_active ? 'checked' : '' }}
                                                        onchange="document.getElementById('zone-toggle-{{ $zone->id }}').submit()"
                                                        class="sr-only peer">
                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-market-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-market-600"></div>
                                                </form>
                                            </label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button onclick="editZone({{ $zone->id }})" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('supplier.shipping.zones.destroy', $zone) }}" onsubmit="return confirm('Are you sure you want to delete this zone?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 mb-3">
                                        @if(is_array($zone->countries))
                                            @foreach($zone->countries as $country)
                                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $country }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $zone->countries ?? 'No countries set' }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $zone->rates->count() }} rate(s)</span>
                                        <a href="{{ route('supplier.shipping.rates', $zone) }}" class="text-sm font-medium text-market-600 dark:text-market-400 hover:underline">View Rates →</a>
                                    </div>
                                </div>
                                <div id="edit-zone-form-{{ $zone->id }}" class="hidden p-5 border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('supplier.shipping.zones.update', $zone) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zone Name</label>
                                                <input type="text" name="name" value="{{ $zone->name }}" required
                                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Countries (comma-separated)</label>
                                                <input type="text" name="countries" value="{{ is_array($zone->countries) ? implode(', ', $zone->countries) : $zone->countries }}"
                                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 mb-4">
                                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <input type="checkbox" name="is_active" value="1" {{ $zone->is_active ? 'checked' : '' }}
                                                    class="rounded border-gray-300 dark:border-gray-600 text-market-600 focus:ring-market-500">
                                                Active
                                            </label>
                                        </div>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" onclick="cancelEditZone({{ $zone->id }})" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-4 py-2 text-sm font-medium bg-market-600 hover:bg-market-700 text-white rounded-xl transition-colors">
                                                Update Zone
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No shipping zones</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Create your first shipping zone to start configuring rates</p>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            function editZone(id) {
                document.getElementById('zone-card-' + id).classList.add('hidden');
                document.getElementById('edit-zone-form-' + id).classList.remove('hidden');
            }
            function cancelEditZone(id) {
                document.getElementById('zone-card-' + id).classList.remove('hidden');
                document.getElementById('edit-zone-form-' + id).classList.add('hidden');
            }
        </script>
    @endpush
</x-app-layout>
