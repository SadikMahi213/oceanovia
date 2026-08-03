<x-app-layout>
    @section('title', 'Wallet')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Wallet</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your earnings and payouts</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-market-500 to-market-700 rounded-2xl p-5 shadow-sm text-white">
                            <p class="text-sm text-market-100">Current Balance</p>
                            <p class="text-3xl font-bold mt-1">${{ number_format($balance?->balance ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pending Balance</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($balance?->pending_balance ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Earned</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($balance?->total_earned ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Withdrawn</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($balance?->total_withdrawn ?? 0, 2) }}</p>
                        </div>
                    </div>

                    <div x-data="{ showPayoutForm: false }" class="mb-6">
                        <button @click="showPayoutForm = !showPayoutForm" class="inline-flex items-center gap-2 px-5 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Request Payout
                        </button>
                        <div x-show="showPayoutForm" x-collapse class="mt-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <form method="POST" action="{{ route('supplier.wallet.payout') }}" class="p-5 space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="amount" value="Amount *" />
                                        <input type="number" step="0.01" min="1" id="amount" name="amount" required placeholder="0.00"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="payment_method" value="Payment Method *" />
                                        <select id="payment_method" name="payment_method" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="bank">Bank Transfer</option>
                                            <option value="paypal">PayPal</option>
                                            <option value="stripe">Stripe</option>
                                            <option value="wise">Wise</option>
                                            <option value="payoneer">Payoneer</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('payment_method')" class="mt-1" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="account_details" value="Account Details *" />
                                    <textarea id="account_details" name="account_details" rows="3" required placeholder="Enter your payment account details..."
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"></textarea>
                                    <x-input-error :messages="$errors->get('account_details')" class="mt-1" />
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showPayoutForm = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Submit Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3">Description</th>
                                        <th class="px-5 py-3">Order</th>
                                        <th class="px-5 py-3 text-right">Amount</th>
                                        <th class="px-5 py-3">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($recentTransactions as $transaction)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $transaction->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white">{{ $transaction->description }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $transaction->order?->order_number ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-right {{ $transaction->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">${{ number_format(abs($transaction->amount), 2) }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $typeColors = ['credit' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'debit' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'payout' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'refund' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$transaction->type] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($transaction->type) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-12 text-center">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No transactions yet</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payout History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Amount</th>
                                        <th class="px-5 py-3 text-right">Fee</th>
                                        <th class="px-5 py-3 text-right">Net</th>
                                        <th class="px-5 py-3">Method</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($payouts as $payout)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payout->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">${{ number_format($payout->fee ?? 0, 2) }}</td>
                                            <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-white text-right">${{ number_format($payout->net_amount ?? $payout->amount, 2) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $payout->payment_method }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    ];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$payout->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($payout->status) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-12 text-center">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">No payout history yet</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($payouts->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $payouts->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>