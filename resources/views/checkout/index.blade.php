<x-app-layout>
    @section('title', 'Checkout')

    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    @endpush

    @auth
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950" x-data="checkoutForm()">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
                <a href="{{ route('home') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('cart.index') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Cart</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 dark:text-white font-medium">Checkout</span>
            </nav>

            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-8">Checkout</h1>

            <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12">
                    {{-- Left: Forms --}}
                    <div class="lg:col-span-3 space-y-6">
                        {{-- Shipping Address --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Shipping Address</h2>
                            @if($addresses && $addresses->count() > 0)
                                <div class="space-y-3 mb-5">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Select a saved address</p>
                                    @foreach($addresses as $address)
                                        <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-market-500 dark:hover:border-market-400 transition-colors" :class="selectedShipping === {{ $address->id }} ? 'border-market-500 dark:border-market-400 bg-market-50 dark:bg-market-900/10' : ''">
                                            <input type="radio" name="shipping_address_id" value="{{ $address->id }}" x-model="selectedShipping" class="mt-1 accent-market-600">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $address->first_name }} {{ $address->last_name }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address->address_line1 }}, {{ $address->city }}, {{ $address->state }} {{ $address->zip }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                    <button type="button" @click="showNewShipping = !showNewShipping" class="text-sm text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 font-medium transition-colors" x-text="showNewShipping ? 'Cancel' : '+ Add new address'"></button>
                                </div>
                            @endif
                            <div x-show="showNewShipping || {{ $addresses->isEmpty() ? 'true' : 'false' }}" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">First Name</label>
                                        <input type="text" name="shipping_first_name" value="{{ Auth::user()->name }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Last Name</label>
                                        <input type="text" name="shipping_last_name" value="{{ Auth::user()->lastname ?? '' }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Street Address</label>
                                    <input type="text" name="shipping_address_line1" required placeholder="123 Main St" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Apartment / Suite (optional)</label>
                                    <input type="text" name="shipping_address_line2" placeholder="Apt 4B" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                                    <input type="tel" name="shipping_phone" required placeholder="+1 (555) 123-4567" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                                <input type="hidden" name="shipping_country" value="US">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">City</label>
                                        <input type="text" name="shipping_city" required placeholder="New York" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">State</label>
                                        <select name="shipping_state" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                            <option value="">Select state</option>
                                            <option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VA">Virginia</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ZIP Code</label>
                                        <input type="text" name="shipping_zip" required placeholder="10001" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                                    <input type="tel" name="shipping_phone" value="{{ Auth::user()->phone ?? '' }}" required placeholder="+1 (555) 123-4567" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                            </div>
                        </div>

                        {{-- Billing Address --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
                            <div class="flex items-start justify-between mb-5">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Billing Address</h2>
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                    <input type="checkbox" x-model="sameAsShipping" @change="if(sameAsShipping) copyShippingToBilling()" class="rounded accent-market-600">
                                    Same as shipping
                                </label>
                            </div>
                            <div :class="sameAsShipping ? 'opacity-50 pointer-events-none' : ''">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">First Name</label><input type="text" name="billing_first_name" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Last Name</label><input type="text" name="billing_last_name" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"></div>
                                </div>
                                <div class="mt-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Street Address</label><input type="text" name="billing_street" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"></div>
                                <div class="mt-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Apartment (optional)</label><input type="text" name="billing_apt" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"></div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">City</label><input type="text" name="billing_city" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">State</label><select name="billing_state" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"><option value="">Select state</option><option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VA">Virginia</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option></select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ZIP Code</label><input type="text" name="billing_zip" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Method --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Payment Method</h2>
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-market-500 dark:hover:border-market-400 transition-colors" :class="paymentMethod === 'stripe' ? 'border-market-500 dark:border-market-400 bg-market-50 dark:bg-market-900/10' : ''">
                                    <input type="radio" name="payment_method" value="stripe" x-model="paymentMethod" class="accent-market-600">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-7 bg-blue-600 rounded flex items-center justify-center text-white text-xs font-bold">VISA</div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Credit or Debit Card</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">We accept Visa, Mastercard, Amex, Discover</p>
                                        </div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-market-500 dark:hover:border-market-400 transition-colors" :class="paymentMethod === 'cod' ? 'border-market-500 dark:border-market-400 bg-market-50 dark:bg-market-900/10' : ''">
                                    <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" class="accent-market-600">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-7 bg-green-600 rounded flex items-center justify-center text-white text-xs font-bold">$</div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Cash on Delivery</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Pay when you receive your order</p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {{-- Stripe Card Element --}}
                            <div x-show="paymentMethod === 'stripe'" x-cloak class="mt-6">
                                <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700">
                                    <div id="card-element"></div>
                                    <div id="card-errors" class="text-red-500 text-xs mt-2"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Place Order --}}
                        <button type="submit" id="place-order-btn" class="w-full py-3.5 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors text-base flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Place Order
                        </button>
                    </div>

                    {{-- Right: Order Summary --}}
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8 sticky top-28">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Order Summary</h2>
                            <div class="space-y-4 mb-6">
                                <template x-for="item in $store.cart.items" :key="item.id">
                                    <div class="flex items-center gap-3">
                                        <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-lg shrink-0 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`Qty: ${item.quantity || 1}`"></p>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="`$${(parseFloat(item.price) * (item.quantity || 1)).toFixed(2)}`"></p>
                                    </div>
                                </template>
                            </div>
                            <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="`$${$store.cart.total.toFixed(2)}`"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">Free</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Tax (8%)</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="`$${($store.cart.total * 0.08).toFixed(2)}`"></span>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                                        <span class="text-xl font-bold text-market-600 dark:text-market-400" x-text="`$${($store.cart.total * 1.08).toFixed(2)}`"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
            function checkoutForm() {
                return {
                    selectedShipping: {{ $addresses?->first()?->id ?? 'null' }},
                    showNewShipping: false,
                    sameAsShipping: true,
                    paymentMethod: 'stripe',
                    copyShippingToBilling() {
                        // Copy will be handled by backend if same_as_shipping flag is set
                    }
                }
            }
        </script>
    </section>
    @else
    {{-- Guest -- Login Prompt --}}
    <section class="py-16 lg:py-24 bg-white dark:bg-gray-950">
        <div class="max-w-lg mx-auto px-4 text-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-8 lg:p-12 shadow-xl">
                <svg class="w-16 h-16 mx-auto text-market-600 dark:text-market-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Sign in to Checkout</h1>
                <p class="text-gray-500 dark:text-gray-400 mb-8">Please sign in to your account or create a new one to complete your purchase.</p>
                <div class="space-y-3">
                    <a href="{{ route('login') }}" class="block w-full py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">Sign In</a>
                    <a href="{{ route('register') }}" class="block w-full py-3 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Create an Account</a>
                    <a href="{{ route('cart.index') }}" class="block text-sm text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors mt-4">← Back to Cart</a>
                </div>
            </div>
        </div>
    </section>
    @endauth
</x-app-layout>
