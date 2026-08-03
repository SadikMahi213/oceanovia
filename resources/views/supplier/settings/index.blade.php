<x-app-layout>
    @section('title', 'Settings')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your store configuration</p>
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

                    <form method="POST" action="{{ route('supplier.settings.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Store Settings</h2>
                            </div>
                            <div class="p-5 space-y-6">
                                <div x-data="{ hours: {{ json_encode(old('working_hours', $profile?->working_hours ?? [])) }} }">
                                    <x-input-label value="Working Hours" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Set open/close times for each day</p>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(day, index) in ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']" :key="day">
                                            <div class="flex items-center gap-3">
                                                <span class="w-24 text-sm text-gray-700 dark:text-gray-300" x-text="day"></span>
                                                <input type="time" :name="'working_hours['+day+'][open]'" x-model="hours[day]?.open || '09:00'"
                                                    class="block w-32 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">to</span>
                                                <input type="time" :name="'working_hours['+day+'][close]'" x-model="hours[day]?.close || '18:00'"
                                                    class="block w-32 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            </div>
                                        </template>
                                    </div>
                                    <x-input-error :messages="$errors->get('working_hours')" class="mt-1" />
                                </div>

                                <div x-data="{ preferences: {{ json_encode(old('shipping_preferences', $profile?->shipping_preferences ?? [])) }} }">
                                    <div class="flex items-center justify-between">
                                        <x-input-label value="Shipping Preferences" />
                                        <button type="button" @click="preferences.push({ key: '', value: '' })" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">+ Add</button>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(pref, index) in preferences" :key="index">
                                            <div class="flex items-center gap-2">
                                                <input type="text" :name="'shipping_preferences['+index+'][key]'" x-model="pref.key" placeholder="Key"
                                                    class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                <input type="text" :name="'shipping_preferences['+index+'][value]'" x-model="pref.value" placeholder="Value"
                                                    class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                <button type="button" @click="preferences.splice(index, 1)" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <x-input-error :messages="$errors->get('shipping_preferences')" class="mt-1" />
                                </div>

                                <div x-data="{ paymentSettings: {{ json_encode(old('payment_settings', $profile?->payment_settings ?? [])) }} }">
                                    <div class="flex items-center justify-between">
                                        <x-input-label value="Payment Settings" />
                                        <button type="button" @click="paymentSettings.push({ key: '', value: '' })" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">+ Add</button>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(setting, index) in paymentSettings" :key="index">
                                            <div class="flex items-center gap-2">
                                                <input type="text" :name="'payment_settings['+index+'][key]'" x-model="setting.key" placeholder="Key"
                                                    class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                <input type="text" :name="'payment_settings['+index+'][value]'" x-model="setting.value" placeholder="Value"
                                                    class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                <button type="button" @click="paymentSettings.splice(index, 1)" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <x-input-error :messages="$errors->get('payment_settings')" class="mt-1" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="notification_email" value="Notification Email" />
                                        <input type="email" id="notification_email" name="notification_email" value="{{ old('notification_email', $profile?->notification_email) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('notification_email')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="language" value="Language" />
                                        <select id="language" name="language"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="en" {{ old('language', $profile?->language) === 'en' ? 'selected' : '' }}>English</option>
                                            <option value="es" {{ old('language', $profile?->language) === 'es' ? 'selected' : '' }}>Spanish</option>
                                            <option value="fr" {{ old('language', $profile?->language) === 'fr' ? 'selected' : '' }}>French</option>
                                            <option value="de" {{ old('language', $profile?->language) === 'de' ? 'selected' : '' }}>German</option>
                                            <option value="ar" {{ old('language', $profile?->language) === 'ar' ? 'selected' : '' }}>Arabic</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('language')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="timezone" value="Timezone" />
                                        <select id="timezone" name="timezone"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="UTC" {{ old('timezone', $profile?->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                                            <option value="America/New_York" {{ old('timezone', $profile?->timezone) === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                            <option value="America/Chicago" {{ old('timezone', $profile?->timezone) === 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                                            <option value="America/Denver" {{ old('timezone', $profile?->timezone) === 'America/Denver' ? 'selected' : '' }}>America/Denver</option>
                                            <option value="America/Los_Angeles" {{ old('timezone', $profile?->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                            <option value="Europe/London" {{ old('timezone', $profile?->timezone) === 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                            <option value="Europe/Paris" {{ old('timezone', $profile?->timezone) === 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris</option>
                                            <option value="Europe/Berlin" {{ old('timezone', $profile?->timezone) === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin</option>
                                            <option value="Asia/Dubai" {{ old('timezone', $profile?->timezone) === 'Asia/Dubai' ? 'selected' : '' }}>Asia/Dubai</option>
                                            <option value="Asia/Tokyo" {{ old('timezone', $profile?->timezone) === 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo</option>
                                            <option value="Asia/Shanghai" {{ old('timezone', $profile?->timezone) === 'Asia/Shanghai' ? 'selected' : '' }}>Asia/Shanghai</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="currency" value="Currency" />
                                        <select id="currency" name="currency"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="USD" {{ old('currency', $profile?->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                            <option value="EUR" {{ old('currency', $profile?->currency) === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                            <option value="GBP" {{ old('currency', $profile?->currency) === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('currency')" class="mt-1" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Settings
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('supplier.settings.password') }}" class="mt-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="current_password" value="Current Password" />
                                    <input type="password" id="current_password" name="current_password" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="password" value="New Password" />
                                    <input type="password" id="password" name="password" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="password_confirmation" value="Confirm New Password" />
                                    <input type="password" id="password_confirmation" name="password_confirmation" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-4">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>