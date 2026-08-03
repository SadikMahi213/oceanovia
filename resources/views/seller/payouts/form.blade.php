<x-app-layout>
    @section('title', 'Request Payout')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Request Payout</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Withdraw your available balance</p>
                        </div>
                        <a href="{{ route('seller.payouts.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Available Balance</h2>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($balance?->balance ?? 0, 2) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Maximum withdrawal amount</p>
                                </div>
                            </div>
                        </div>
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

                    <form method="POST" action="{{ route('seller.payouts.store') }}" class="space-y-6">
                        @csrf

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payout Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="amount" value="Amount *" />
                                    <div class="mt-1 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">$</span>
                                        <input type="number" step="0.01" min="0" max="{{ $balance?->balance ?? 0 }}" id="amount" name="amount" value="{{ old('amount') }}" required
                                            class="block w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maximum: ${{ number_format($balance?->balance ?? 0, 2) }}</p>
                                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="payment_method" value="Payment Method *" />
                                    <select id="payment_method" name="payment_method" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="">Select a method</option>
                                        <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="stripe" {{ old('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                                        <option value="paypal" {{ old('payment_method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('payment_method')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="account_details" value="Account Details *" />
                                    <textarea id="account_details" name="account_details" rows="4" required
                                        placeholder='{"account_holder": "John Doe", "account_number": "****", "routing_number": "****"}'
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm font-mono">{{ old('account_details') }}</textarea>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Provide your payment account details in JSON format</p>
                                    <x-input-error :messages="$errors->get('account_details')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('seller.payouts.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Submit Payout Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
