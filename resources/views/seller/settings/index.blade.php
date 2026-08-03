<x-app-layout>
    @section('title', 'Settings')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure your store preferences</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Store Settings</h2>
                        </div>
                        <div class="p-5">
                            <form method="POST" action="{{ route('seller.settings.update') }}" class="space-y-6">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="default_return_period_days" value="Default Return Period (Days)" />
                                        <input type="number" id="default_return_period_days" name="default_return_period_days" min="0"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('default_return_period_days')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="low_stock_threshold" value="Low Stock Threshold" />
                                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('low_stock_threshold')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="store_notifications_email" value="Store Notifications Email" />
                                        <input type="email" id="store_notifications_email" name="store_notifications_email"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('store_notifications_email')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="allow_backorders" value="1"
                                            class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 focus:ring-market-500">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Allow Backorders</span>
                                    </label>

                                    <label class="flex items-center gap-3">
                                        <input type="checkbox" name="auto_fulfill_orders" value="1"
                                            class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 focus:ring-market-500">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Auto-Fulfill Orders</span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                        </div>
                        <div class="p-5">
                            <form method="POST" action="{{ route('seller.settings.password') }}" class="space-y-6">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="current_password" value="Current Password" />
                                        <input type="password" id="current_password" name="current_password"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="password" value="New Password" />
                                        <input type="password" id="password" name="password"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="password_confirmation" value="Confirm New Password" />
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Update Password
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
