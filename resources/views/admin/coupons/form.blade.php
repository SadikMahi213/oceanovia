@php
$edit = isset($coupon);
$title = $edit ? 'Edit Coupon' : 'Add Coupon';
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update coupon details' : 'Create a new coupon' }}</p>
                        </div>
                        <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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

                    <form method="POST" action="{{ $edit ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Coupon Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="code" value="Coupon Code *" />
                                    <input type="text" id="code" name="code" value="{{ old('code', $coupon?->code) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm uppercase">
                                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="description" value="Description" />
                                    <textarea id="description" name="description" rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('description', $coupon?->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Discount Configuration</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="type" value="Discount Type *" />
                                    <select id="type" name="type" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="percentage" {{ old('type', $coupon?->type) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="fixed" {{ old('type', $coupon?->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="value" value="Discount Value *" />
                                    <div class="mt-1 relative">
                                        <input type="number" step="0.01" min="0" id="value" name="value" value="{{ old('value', $coupon?->value) }}" required
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('value')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="min_order_amount" value="Minimum Order Amount" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="min_order_amount" name="min_order_amount" value="{{ old('min_order_amount', $coupon?->min_order_amount) }}"
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('min_order_amount')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="max_discount" value="Maximum Discount" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" id="max_discount" name="max_discount" value="{{ old('max_discount', $coupon?->max_discount) }}"
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <x-input-error :messages="$errors->get('max_discount')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Validity & Limits</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="usage_limit" value="Total Usage Limit" />
                                    <input type="number" min="0" id="usage_limit" name="usage_limit" value="{{ old('usage_limit', $coupon?->usage_limit) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty for unlimited</p>
                                    <x-input-error :messages="$errors->get('usage_limit')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="per_user_limit" value="Per-User Limit" />
                                    <input type="number" min="1" id="per_user_limit" name="per_user_limit" value="{{ old('per_user_limit', $coupon?->per_user_limit ?? 1) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('per_user_limit')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="starts_at" value="Start Date" />
                                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $coupon?->starts_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('starts_at')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="expires_at" value="Expiry Date" />
                                    <input type="datetime-local" id="expires_at" name="expires_at" value="{{ old('expires_at', $coupon?->expires_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $coupon?->is_active ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 shadow-sm focus:ring-market-500">
                                    <x-input-label for="is_active" value="Active" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Coupon' : 'Create Coupon' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
