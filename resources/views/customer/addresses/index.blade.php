<x-app-layout>
    @section('title', 'My Addresses')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Addresses</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your shipping and billing addresses</p>
                        </div>
                    </div>

                    <div x-data="{ showAddForm: false }" class="mb-6">
                        <button @click="showAddForm = !showAddForm" class="inline-flex items-center gap-2 px-5 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add New Address
                        </button>

                        <div x-show="showAddForm" x-collapse class="mt-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <form method="POST" action="{{ route('customer.addresses.store') }}" class="p-5 space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="address_type" value="Address Type *" />
                                        <select id="address_type" name="address_type" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="shipping">Shipping</option>
                                            <option value="billing">Billing</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('address_type')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="first_name" value="First Name *" />
                                        <input type="text" id="first_name" name="first_name" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="last_name" value="Last Name *" />
                                        <input type="text" id="last_name" name="last_name" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="phone" value="Phone" />
                                        <input type="text" id="phone" name="phone"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label for="address_line1" value="Address Line 1 *" />
                                        <input type="text" id="address_line1" name="address_line1" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('address_line1')" class="mt-1" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label for="address_line2" value="Address Line 2" />
                                        <input type="text" id="address_line2" name="address_line2"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('address_line2')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="city" value="City *" />
                                        <input type="text" id="city" name="city" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('city')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="state" value="State *" />
                                        <input type="text" id="state" name="state" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('state')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="zip" value="ZIP Code *" />
                                        <input type="text" id="zip" name="zip" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('zip')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="country" value="Country *" />
                                        <input type="text" id="country" name="country" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('country')" class="mt-1" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="is_default" value="0">
                                    <input type="checkbox" id="is_default" name="is_default" value="1"
                                        class="rounded border-gray-300 dark:border-gray-600 text-market-600 shadow-sm focus:ring-market-500">
                                    <x-input-label for="is_default" value="Set as default address" />
                                </div>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="showAddForm = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Save Address</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if($addresses && $addresses->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($addresses as $address)
                                <div x-data="{ showEditForm: false }" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div x-show="!showEditForm" class="p-5">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $address->address_type === 'billing' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">{{ ucfirst($address->address_type) }}</span>
                                                @if($address->is_default)
                                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-market-100 text-market-800 dark:bg-market-900/30 dark:text-market-400">Default</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button @click="showEditForm = true" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Edit</button>
                                                <form method="POST" action="{{ route('customer.addresses.destroy', $address) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this address?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $address->first_name }} {{ $address->last_name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $address->address_line1 }}</p>
                                        @if($address->address_line2)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address->address_line2 }}</p>
                                        @endif
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address->city }}, {{ $address->state }} {{ $address->zip }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $address->country }}</p>
                                        @if($address->phone)
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $address->phone }}</p>
                                        @endif
                                    </div>

                                    <div x-show="showEditForm" x-cloak class="p-5">
                                        <form method="POST" action="{{ route('customer.addresses.update', $address) }}" class="space-y-4">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <x-input-label for="address_type_{{ $address->id }}" value="Address Type *" />
                                                    <select id="address_type_{{ $address->id }}" name="address_type" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                        <option value="shipping" {{ $address->address_type === 'shipping' ? 'selected' : '' }}>Shipping</option>
                                                        <option value="billing" {{ $address->address_type === 'billing' ? 'selected' : '' }}>Billing</option>
                                                    </select>
                                                    <x-input-error :messages="$errors->get('address_type')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="first_name_{{ $address->id }}" value="First Name *" />
                                                    <input type="text" id="first_name_{{ $address->id }}" name="first_name" value="{{ old('first_name', $address->first_name) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="last_name_{{ $address->id }}" value="Last Name *" />
                                                    <input type="text" id="last_name_{{ $address->id }}" name="last_name" value="{{ old('last_name', $address->last_name) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="phone_{{ $address->id }}" value="Phone" />
                                                    <input type="text" id="phone_{{ $address->id }}" name="phone" value="{{ old('phone', $address->phone) }}"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                                </div>
                                                <div class="md:col-span-2">
                                                    <x-input-label for="address_line1_{{ $address->id }}" value="Address Line 1 *" />
                                                    <input type="text" id="address_line1_{{ $address->id }}" name="address_line1" value="{{ old('address_line1', $address->address_line1) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('address_line1')" class="mt-1" />
                                                </div>
                                                <div class="md:col-span-2">
                                                    <x-input-label for="address_line2_{{ $address->id }}" value="Address Line 2" />
                                                    <input type="text" id="address_line2_{{ $address->id }}" name="address_line2" value="{{ old('address_line2', $address->address_line2) }}"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('address_line2')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="city_{{ $address->id }}" value="City *" />
                                                    <input type="text" id="city_{{ $address->id }}" name="city" value="{{ old('city', $address->city) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('city')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="state_{{ $address->id }}" value="State *" />
                                                    <input type="text" id="state_{{ $address->id }}" name="state" value="{{ old('state', $address->state) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('state')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="zip_{{ $address->id }}" value="ZIP Code *" />
                                                    <input type="text" id="zip_{{ $address->id }}" name="zip" value="{{ old('zip', $address->zip) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('zip')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label for="country_{{ $address->id }}" value="Country *" />
                                                    <input type="text" id="country_{{ $address->id }}" name="country" value="{{ old('country', $address->country) }}" required
                                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                    <x-input-error :messages="$errors->get('country')" class="mt-1" />
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="hidden" name="is_default" value="0">
                                                <input type="checkbox" id="is_default_{{ $address->id }}" name="is_default" value="1" {{ $address->is_default ? 'checked' : '' }}
                                                    class="rounded border-gray-300 dark:border-gray-600 text-market-600 shadow-sm focus:ring-market-500">
                                                <x-input-label for="is_default_{{ $address->id }}" value="Set as default address" />
                                            </div>
                                            <div class="flex items-center justify-end gap-3">
                                                <button type="button" @click="showEditForm = false" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Update Address</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No addresses yet</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add a shipping or billing address to get started</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
