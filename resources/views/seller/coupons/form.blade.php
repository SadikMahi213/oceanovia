<x-app-layout>
    @section('title', isset($coupon) ? 'Edit Coupon' : 'Create Coupon')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isset($coupon) ? 'Edit Coupon' : 'Create Coupon' }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ isset($coupon) ? 'Update coupon details' : 'Create a new discount coupon' }}</p>
                        </div>
                        <a href="{{ route('seller.coupons.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Coupons
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ isset($coupon) ? 'Edit Coupon' : 'Coupon Details' }}</h2>
                        </div>
                        <div class="p-5">
                            <form method="POST" action="{{ isset($coupon) ? route('seller.coupons.update', $coupon) : route('seller.coupons.store') }}" class="space-y-6">
                                @csrf
                                @if(isset($coupon))
                                    @method('PUT')
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="code" value="Code" />
                                        <input id="code" name="code" type="text" value="{{ old('code', $coupon->code ?? '') }}" required class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="type" value="Discount Type" />
                                        <select id="type" name="type" required class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                            <option value="percentage" {{ old('type', $coupon->type ?? '') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                            <option value="fixed" {{ old('type', $coupon->type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="value" value="Discount Value" />
                                        <input id="value" name="value" type="number" step="0.01" value="{{ old('value', $coupon->value ?? '') }}" required class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                        <x-input-error :messages="$errors->get('value')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="min_order_amount" value="Minimum Order Amount" />
                                        <input id="min_order_amount" name="min_order_amount" type="number" step="0.01" value="{{ old('min_order_amount', $coupon->min_order_amount ?? '') }}" class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                        <x-input-error :messages="$errors->get('min_order_amount')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="usage_limit" value="Usage Limit" />
                                        <input id="usage_limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                        <x-input-error :messages="$errors->get('usage_limit')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="starts_at" value="Start Date" />
                                        <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', isset($coupon) && $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}" class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                        <x-input-error :messages="$errors->get('starts_at')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="expires_at" value="Expiry Date" />
                                        <input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', isset($coupon) && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}" class="mt-1 block w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500">
                                        <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <input type="hidden" name="is_active" value="0">
                                        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-market-600 focus:ring-market-500">
                                        <x-input-label for="is_active" value="Active" class="!mb-0" />
                                        <x-input-error :messages="$errors->get('is_active')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                        {{ isset($coupon) ? 'Update Coupon' : 'Create Coupon' }}
                                    </button>
                                    <a href="{{ route('seller.coupons.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-colors">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
