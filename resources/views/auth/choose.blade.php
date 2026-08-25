<x-guest-layout>
    @section('auth_title', 'Welcome to MulitVendor')
    @section('auth_subtitle', 'Choose how you want to use the platform')
    <div class="space-y-4">
        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-6">Select your account type to continue — you can always switch later.</p>

        <div class="grid grid-cols-1 gap-4">
            <!-- Customer -->
            <div class="p-4 border-2 rounded-2xl bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-market-300 dark:hover:border-market-700 transition-colors">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Customer</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Shop products, browse stores, place orders and track your purchases.</p>
                        <div class="mt-3 flex items-center gap-2">
                            <a href="{{ route('login', ['role'=>'customer']) }}" class="inline-flex items-center justify-center px-4 py-2 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Login as Customer</a>
                            <a href="{{ route('register', ['role'=>'customer']) }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Create Customer Account</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seller -->
            <div class="p-4 border-2 rounded-2xl bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-market-300 dark:hover:border-market-700 transition-colors">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Seller</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sell your products, manage your store and receive customer orders.</p>
                        <div class="mt-3 flex items-center gap-2">
                            <a href="{{ route('login', ['role'=>'seller']) }}" class="inline-flex items-center justify-center px-4 py-2 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Login as Seller</a>
                            <a href="{{ route('register', ['role'=>'seller']) }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Become a Seller</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supplier -->
            <div class="p-4 border-2 rounded-2xl bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-market-300 dark:hover:border-market-700 transition-colors">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Supplier</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Supply products, manage supplier orders and grow your business.</p>
                        <div class="mt-3 flex items-center gap-2">
                            <a href="{{ route('login', ['role'=>'supplier']) }}" class="inline-flex items-center justify-center px-4 py-2 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Login as Supplier</a>
                            <a href="{{ route('register', ['role'=>'supplier']) }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Register as Supplier</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-xs text-center text-gray-400 dark:text-gray-500 mt-4">Already know your account type? Use the buttons above — you can switch role anytime on the login screen.</p>
    </div>
</x-guest-layout>
