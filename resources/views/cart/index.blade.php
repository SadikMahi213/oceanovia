<x-app-layout>
    @section('title', 'Shopping Cart')

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-10 lg:py-14">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-3">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">Cart</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">Shopping Cart</h1>
            <p class="text-market-200 mt-1" x-text="`${$store.cart.count} item${$store.cart.count !== 1 ? 's' : ''} in your cart`"></p>
        </div>
    </section>

    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950" x-data>
        <div class="max-w-7xl mx-auto px-4" x-data="{ cartLoaded: false }" x-init="setTimeout(() => cartLoaded = true, 500)">
            {{-- Empty State --}}
            <div x-show="cartLoaded && $store.cart.count === 0" x-cloak class="text-center py-16 lg:py-24">
                <svg class="w-24 h-24 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Your cart is empty</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">Looks like you haven't added anything yet. Start exploring our marketplace and find something you love!</p>
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-market-600/20">
                    Start Shopping
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>

            {{-- Cart Content --}}
            <div x-show="cartLoaded && $store.cart.count > 0" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
                {{-- Cart Items --}}
                <div class="lg:col-span-2 space-y-4">
                    <template x-for="(item, index) in $store.cart.items" :key="item.id">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 lg:p-6 flex items-center gap-4">
                            {{-- Image placeholder --}}
                            <div class="w-20 h-20 lg:w-24 lg:h-24 bg-gray-100 dark:bg-gray-700 rounded-xl shrink-0 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="item.name"></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-text="`$${parseFloat(item.price).toFixed(2)} each`"></p>
                                <div class="flex items-center gap-3 mt-3">
                                    {{-- Qty Stepper --}}
                                    <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <button @click="$store.cart.updateQuantity(item.id, Math.max(1, (item.quantity || 1) - 1))" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                        <span class="w-10 text-center text-sm font-medium" x-text="item.quantity || 1"></span>
                                        <button @click="$store.cart.updateQuantity(item.id, Math.min(99, (item.quantity || 1) + 1))" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="`$${(parseFloat(item.price) * (item.quantity || 1)).toFixed(2)}`"></span>
                                </div>
                            </div>
                            {{-- Remove --}}
                            <button @click="$store.cart.removeItem(item.id); $store.toast.info('Removed from cart')" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Order Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 sticky top-28">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Order Summary</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="`$${$store.cart.total.toFixed(2)}`"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                <span class="font-medium" :class="$store.cart.shipping === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'" x-text="$store.cart.shipping === 0 ? 'Free' : `$${$store.cart.shipping.toFixed(2)}`"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="`$${$store.cart.tax.toFixed(2)}`"></span>
                            </div>
                            <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                                    <span class="text-lg font-bold text-market-600 dark:text-market-400" x-text="`$${$store.cart.grandTotal.toFixed(2)}`"></span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Including estimated tax</p>
                            </div>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="block w-full mt-6 py-3 bg-market-600 hover:bg-market-700 text-white text-center font-medium rounded-xl transition-colors">
                            Proceed to Checkout
                        </a>
                        <a href="{{ route('products.index') }}" class="block w-full mt-3 py-3 text-center text-sm text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 font-medium transition-colors">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
