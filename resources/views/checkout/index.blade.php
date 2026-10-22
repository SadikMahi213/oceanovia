<x-app-layout>
    @section('title', 'Checkout')

    @push('scripts')
        @if (\App\Support\PaymentGateway::stripeConfigured())
            <script src="https://js.stripe.com/v3/"></script>
        @endif
    @endpush

    @php
        $usStates = [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        ];

        $oldShippingId = old('shipping_address_id');
        $hasOldNewShipping = old('shipping_first_name') !== null;
        $oldShippingIdValid = $oldShippingId && $shippingAddresses->contains('id', (int) $oldShippingId);
        $selectedShippingId = $hasOldNewShipping
            ? null
            : ($oldShippingIdValid ? (int) $oldShippingId : $shippingAddresses->first()?->id);
        $showNewShipping = $shippingAddresses->isEmpty()
            || $hasOldNewShipping
            || ($oldShippingId && ! $oldShippingIdValid);

        $oldBillingId = old('billing_address_id');
        $selectedBillingId = $oldBillingId && $billingAddresses->contains('id', (int) $oldBillingId)
            ? (int) $oldBillingId
            : null;
        $defaultShippingId = $shippingAddresses->first()?->id;
        $billingSame = old('billing_same', '1') == '1';
    @endphp

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
                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
                        <p class="font-semibold mb-2">Please correct the following:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12">
                    {{-- Left: Forms --}}
                    <div class="lg:col-span-3 space-y-6">
                        {{-- Shipping Address --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Shipping Address</h2>
                            @if($shippingAddresses->isNotEmpty())
                                <div class="space-y-3 mb-5">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        @if($shippingUsingBillingFallback ?? false)
                                            No shipping address is saved yet. Select one of your billing addresses to use for shipping.
                                        @else
                                            Select a saved address
                                        @endif
                                    </p>
                                    @foreach($shippingAddresses as $address)
                                        <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-market-500 dark:hover:border-market-400 transition-colors" :class="selectedShipping == {{ $address->id }} ? 'border-market-500 dark:border-market-400 bg-market-50 dark:bg-market-900/10' : ''">
                                            <input type="radio" name="shipping_address_id" value="{{ $address->id }}" x-model="selectedShipping" @change="showNewShipping = false" class="mt-1 accent-market-600">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $address->first_name }} {{ $address->last_name }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address->address_line1 }}, {{ $address->city }}, {{ $address->state }} {{ $address->zip }}</p>
                                                @if($shippingUsingBillingFallback ?? false)
                                                    <p class="text-xs font-medium text-market-600 dark:text-market-400 mt-1">Billing address</p>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                    <button type="button" @click="toggleNewShipping()" class="text-sm text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 font-medium transition-colors" x-text="showNewShipping ? 'Cancel' : '+ Add new address'"></button>
                                </div>
                            @endif
                            <fieldset x-show="showNewShipping" :disabled="!showNewShipping" class="space-y-4 min-w-0 border-0 p-0 m-0">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="shipping_first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">First Name</label>
                                        <input id="shipping_first_name" type="text" name="shipping_first_name" value="{{ old('shipping_first_name', Auth::user()->name) }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <div>
                                        <label for="shipping_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Last Name</label>
                                        <input id="shipping_last_name" type="text" name="shipping_last_name" value="{{ old('shipping_last_name', Auth::user()->lastname ?? '') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                </div>
                                <div>
                                    <label for="shipping_address_line1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Street Address</label>
                                    <input id="shipping_address_line1" type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1') }}" required placeholder="123 Main St" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                                <div>
                                    <label for="shipping_address_line2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Apartment / Suite (optional)</label>
                                    <input id="shipping_address_line2" type="text" name="shipping_address_line2" value="{{ old('shipping_address_line2') }}" placeholder="Apt 4B" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                                <div>
                                    <label for="shipping_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                                    <input id="shipping_phone" type="tel" name="shipping_phone" value="{{ old('shipping_phone', Auth::user()->phone ?? '') }}" required placeholder="+1 (555) 123-4567" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                </div>
                                <input type="hidden" name="shipping_country" value="{{ old('shipping_country', 'US') }}">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="shipping_city" class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">City</label>
                                        <input id="shipping_city" type="text" name="shipping_city" value="{{ old('shipping_city') }}" required placeholder="New York" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <div>
                                        <label for="shipping_state" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">State</label>
                                        <select id="shipping_state" name="shipping_state" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                            <option value="">Select state</option>
                                            @foreach($usStates as $code => $label)
                                                <option value="{{ $code }}" @selected(old('shipping_state') === $code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="shipping_zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ZIP Code</label>
                                        <input id="shipping_zip" type="text" name="shipping_zip" value="{{ old('shipping_zip') }}" required placeholder="10001" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        {{-- Billing Address --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
                            <div class="flex items-start justify-between mb-5">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Billing Address</h2>
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                    <input type="hidden" name="billing_same" value="0">
                                    <input type="checkbox" name="billing_same" value="1" x-model="sameAsShipping" class="rounded accent-market-600">
                                    Same as shipping
                                </label>
                            </div>
                            <div x-show="!sameAsShipping" class="space-y-4">
                                @if($billingAddresses->isNotEmpty())
                                    <fieldset :disabled="sameAsShipping" class="space-y-3 min-w-0 border-0 p-0 m-0">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Select a saved billing address</p>
                                        @foreach($billingAddresses as $address)
                                            <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-market-500 dark:hover:border-market-400 transition-colors" :class="selectedBilling == {{ $address->id }} ? 'border-market-500 dark:border-market-400 bg-market-50 dark:bg-market-900/10' : ''">
                                                <input type="radio" name="billing_address_id" value="{{ $address->id }}" x-model="selectedBilling" class="mt-1 accent-market-600">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $address->first_name }} {{ $address->last_name }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address->address_line1 }}, {{ $address->city }}, {{ $address->state }} {{ $address->zip }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                        <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-market-500 dark:hover:border-market-400 transition-colors" :class="!selectedBilling ? 'border-market-500 dark:border-market-400 bg-market-50 dark:bg-market-900/10' : ''">
                                            <input type="radio" name="billing_address_id" value="" x-model="selectedBilling" class="mt-1 accent-market-600">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">Use a new billing address</p>
                                            </div>
                                        </label>
                                    </fieldset>
                                @endif
                                <fieldset x-show="!selectedBilling" :disabled="sameAsShipping || !!selectedBilling" class="space-y-4 min-w-0 border-0 p-0 m-0">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="billing_first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">First Name</label>
                                            <input id="billing_first_name" type="text" name="billing_first_name" value="{{ old('billing_first_name') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                        </div>
                                        <div>
                                            <label for="billing_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Last Name</label>
                                            <input id="billing_last_name" type="text" name="billing_last_name" value="{{ old('billing_last_name') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="billing_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                                            <input id="billing_phone" type="tel" name="billing_phone" value="{{ old('billing_phone') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                        </div>
                                        <div>
                                            <label for="billing_country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Country</label>
                                            <select id="billing_country" name="billing_country" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                                <option value="US" @selected(old('billing_country', 'US') === 'US')>United States</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="billing_street" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Street Address</label>
                                        <input id="billing_street" type="text" name="billing_street" value="{{ old('billing_street') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <div>
                                        <label for="billing_apt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Apartment (optional)</label>
                                        <input id="billing_apt" type="text" name="billing_apt" value="{{ old('billing_apt') }}" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label for="billing_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">City</label>
                                            <input id="billing_city" type="text" name="billing_city" value="{{ old('billing_city') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                        </div>
                                        <div>
                                            <label for="billing_state" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">State</label>
                                            <select id="billing_state" name="billing_state" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                                <option value="">Select state</option>
                                                @foreach($usStates as $code => $label)
                                                    <option value="{{ $code }}" @selected(old('billing_state') === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="billing_zip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ZIP Code</label>
                                            <input id="billing_zip" type="text" name="billing_zip" value="{{ old('billing_zip') }}" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        {{-- Payment Method --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Payment Method</h2>
                            <div class="space-y-4">
                                @if (\App\Support\PaymentGateway::stripeConfigured())
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
                                @endif
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

                            @if (\App\Support\PaymentGateway::stripeConfigured())
                            {{-- Stripe Card Element --}}
                            <div x-show="paymentMethod === 'stripe'" x-cloak class="mt-6">
                                <div class="p-4 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700">
                                    <div id="card-element"></div>
                                    <div id="card-errors" class="text-red-500 text-xs mt-2"></div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Place Order --}}
                        <button type="submit" id="place-order-btn" class="w-full py-3.5 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors text-base flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
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
                                    <span class="font-medium" :class="$store.cart.shipping === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'" x-text="$store.cart.shipping === 0 ? 'Free' : `$${$store.cart.shipping.toFixed(2)}`"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="`$${$store.cart.tax.toFixed(2)}`"></span>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                                        <span class="text-xl font-bold text-market-600 dark:text-market-400" x-text="`$${$store.cart.grandTotal.toFixed(2)}`"></span>
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
                    selectedShipping: @json($selectedShippingId !== null ? (string) $selectedShippingId : null),
                    showNewShipping: @json((bool) $showNewShipping),
                    defaultShipping: @json($defaultShippingId !== null ? (string) $defaultShippingId : null),
                    sameAsShipping: @json($billingSame),
                    selectedBilling: @json($selectedBillingId !== null ? (string) $selectedBillingId : ''),
                    paymentMethod: @json(\App\Support\PaymentGateway::resolveSelection(old('payment_method'))),
                    toggleNewShipping() {
                        if (this.showNewShipping) {
                            this.showNewShipping = false;
                            this.selectedShipping = this.defaultShipping;
                        } else {
                            this.showNewShipping = true;
                            this.selectedShipping = null;
                        }
                    }
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('checkout-form');
                const submitButton = document.getElementById('place-order-btn');

                if (form && submitButton) {
                    form.addEventListener('submit', function() {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Placing Order...';
                    });
                }

                @if (\App\Support\PaymentGateway::stripeConfigured())
                if (typeof Stripe !== 'undefined') {
                    const stripe = Stripe('{{ config('services.stripe.key') }}');
                    const elements = stripe.elements();
                    const cardElement = elements.create('card', {
                        style: {
                            base: {
                                fontSize: '14px',
                                color: '#374151',
                                '::placeholder': { color: '#9ca3af' }
                            }
                        }
                    });
                    const mountPoint = document.getElementById('card-element');
                    if (mountPoint) {
                        cardElement.mount('#card-element');
                        cardElement.on('change', function(event) {
                            const displayError = document.getElementById('card-errors');
                            if (event.error) {
                                displayError.textContent = event.error.message;
                            } else {
                                displayError.textContent = '';
                            }
                        });
                    }
                }
                @endif
            });
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
