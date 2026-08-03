<x-app-layout>
    @section('title', 'Withdrawal Methods')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Withdrawal Methods</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your payout withdrawal methods</p>
                        </div>
                    </div>

                    @if(count($methods) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            @foreach($methods as $index => $method)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-5">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-3">
                                                @php
                                                    $typeInfo = match($method['type'] ?? 'bank') {
                                                        'bank' => ['bg-blue-50 dark:bg-blue-900/30', 'text-blue-600 dark:text-blue-400', 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
                                                        'paypal' => ['bg-indigo-50 dark:bg-indigo-900/30', 'text-indigo-600 dark:text-indigo-400', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                                                        'mobile_banking' => ['bg-green-50 dark:bg-green-900/30', 'text-green-600 dark:text-green-400', 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                                                        'wise' => ['bg-purple-50 dark:bg-purple-900/30', 'text-purple-600 dark:text-purple-400', 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
                                                        'payoneer' => ['bg-cyan-50 dark:bg-cyan-900/30', 'text-cyan-600 dark:text-cyan-400', 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0'],
                                                        'crypto' => ['bg-orange-50 dark:bg-orange-900/30', 'text-orange-600 dark:text-orange-400', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                                        default => ['bg-gray-50 dark:bg-gray-700', 'text-gray-600 dark:text-gray-400', 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
                                                    };
                                                @endphp
                                                <div class="w-10 h-10 rounded-xl {{ $typeInfo[0] }} flex items-center justify-center">
                                                    <svg class="w-5 h-5 {{ $typeInfo[1] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeInfo[2] }}"/></svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $method['type'] ?? 'bank') }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono">{{ Str::limit(json_encode($method['details'] ?? []), 60) }}</p>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('seller.payouts.methods.destroy', $index) }}" onsubmit="return confirm('Delete this withdrawal method?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-8">
                            <div class="p-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No withdrawal methods yet</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add a withdrawal method to receive your payouts</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add Method</h2>
                        </div>
                        <div class="p-5">
                            <form method="POST" action="{{ route('seller.payouts.methods.store') }}" class="space-y-6">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="type" value="Payment Type" />
                                        <select id="type" name="type"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="bank">Bank Transfer</option>
                                            <option value="paypal">PayPal</option>
                                            <option value="mobile_banking">Mobile Banking</option>
                                            <option value="wise">Wise</option>
                                            <option value="payoneer">Payoneer</option>
                                            <option value="crypto">Cryptocurrency</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="details" value="Details (JSON)" />
                                    <textarea id="details" name="details" rows="4" placeholder='{"account_name": "John Doe", "account_number": "1234567890", "bank_name": "Example Bank"}'
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm font-mono"></textarea>
                                    <x-input-error :messages="$errors->get('details')" class="mt-1" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter the method details as a JSON object</p>
                                </div>
                                <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Add Method
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
