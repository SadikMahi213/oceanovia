@php
$edit = isset($shippingMethod);
$title = $edit ? 'Edit Shipping Method' : 'Add Shipping Method';
@endphp
<x-app-layout>
    @section('title', $title)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update shipping method details' : 'Create a new shipping method' }}</p>
                        </div>
                        <a href="{{ route('admin.shipping-methods.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </a>
                    </div>

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">Please fix the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $edit ? route('admin.shipping-methods.update', $shippingMethod) : route('admin.shipping-methods.store') }}" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Shipping Method Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="name" value="Name *" />
                                        <input type="text" id="name" name="name" value="{{ old('name', $shippingMethod?->name) }}" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="code" value="Code *" />
                                        <input type="text" id="code" name="code" value="{{ old('code', $shippingMethod?->code) }}" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm uppercase">
                                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="description" value="Description" />
                                    <textarea id="description" name="description" rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('description', $shippingMethod?->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pricing</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="base_rate" value="Base Rate *" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="base_rate" name="base_rate" value="{{ old('base_rate', $shippingMethod?->base_rate) }}" required
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('base_rate')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="rate_per_kg" value="Rate per Kg *" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="rate_per_kg" name="rate_per_kg" value="{{ old('rate_per_kg', $shippingMethod?->rate_per_kg) }}" required
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('rate_per_kg')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="free_shipping_threshold" value="Free Shipping Threshold" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="free_shipping_threshold" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $shippingMethod?->free_shipping_threshold) }}"
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to disable</p>
                                    <x-input-error :messages="$errors->get('free_shipping_threshold')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delivery Estimates & Zones</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="estimated_days_min" value="Estimated Days (Min)" />
                                    <input type="number" min="0" id="estimated_days_min" name="estimated_days_min" value="{{ old('estimated_days_min', $shippingMethod?->estimated_days_min) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('estimated_days_min')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="estimated_days_max" value="Estimated Days (Max)" />
                                    <input type="number" min="0" id="estimated_days_max" name="estimated_days_max" value="{{ old('estimated_days_max', $shippingMethod?->estimated_days_max) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('estimated_days_max')" class="mt-1" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="zones" value="Zones (JSON)" />
                                    <textarea id="zones" name="zones" rows="4"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm font-mono">{{ old('zones', $shippingMethod?->zones ? json_encode($shippingMethod->zones, JSON_PRETTY_PRINT) : '') }}</textarea>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter zones as a JSON array, e.g. [{"region":"US","rate":5.99}]</p>
                                    <x-input-error :messages="$errors->get('zones')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="sort_order" value="Sort Order" />
                                    <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $shippingMethod?->sort_order ?? 0) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
                                </div>
                                <div class="flex items-end pb-1">
                                <div class="flex items-center gap-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $shippingMethod?->is_active ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 shadow-sm focus:ring-market-500">
                                    <x-input-label for="is_active" value="Active" />
                                </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.shipping-methods.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Shipping Method' : 'Create Shipping Method' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
