<x-app-layout>
    @section('title', 'Settings')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your preferences and security</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('customer.settings.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Preferences</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                @php
                                    $prefs = $user->notification_preferences ?? [];
                                    if (is_string($prefs)) $prefs = json_decode($prefs, true) ?? [];
                                @endphp
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Email Notifications</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Receive order and account notifications via email</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="notification_preferences[email]" value="1" {{ ($prefs['email'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-market-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-market-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Order Updates</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Get notified when your order status changes</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="notification_preferences[order_updates]" value="1" {{ ($prefs['order_updates'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-market-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-market-600"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Promotions & Offers</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Receive promotional emails and special offers</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="notification_preferences[promotions]" value="1" {{ ($prefs['promotions'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-market-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-market-600"></div>
                                    </label>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <div>
                                        <x-input-label for="language" value="Language" />
                                        <select id="language" name="language"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="en" {{ old('language', $prefs['language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                                            <option value="es" {{ old('language', $prefs['language'] ?? 'en') == 'es' ? 'selected' : '' }}>Español</option>
                                            <option value="fr" {{ old('language', $prefs['language'] ?? 'en') == 'fr' ? 'selected' : '' }}>Français</option>
                                            <option value="de" {{ old('language', $prefs['language'] ?? 'en') == 'de' ? 'selected' : '' }}>Deutsch</option>
                                            <option value="ar" {{ old('language', $prefs['language'] ?? 'en') == 'ar' ? 'selected' : '' }}>العربية</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('language')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="timezone" value="Timezone" />
                                        <select id="timezone" name="timezone"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="UTC" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                            <option value="America/New_York" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'America/New_York' ? 'selected' : '' }}>America/New York</option>
                                            <option value="America/Chicago" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                                            <option value="America/Denver" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'America/Denver' ? 'selected' : '' }}>America/Denver</option>
                                            <option value="America/Los_Angeles" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'America/Los_Angeles' ? 'selected' : '' }}>America/Los Angeles</option>
                                            <option value="Europe/London" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                            <option value="Europe/Paris" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris</option>
                                            <option value="Asia/Dubai" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'Asia/Dubai' ? 'selected' : '' }}>Asia/Dubai</option>
                                            <option value="Asia/Tokyo" {{ old('timezone', $prefs['timezone'] ?? 'UTC') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
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

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mt-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                        </div>
                        <form method="POST" action="{{ route('customer.profile.password') }}" class="p-5 space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                    <x-input-label for="password_confirmation" value="Confirm Password" />
                                    <input type="password" id="password_confirmation" name="password_confirmation" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-200 text-white dark:text-gray-900 text-sm font-medium rounded-xl transition-colors">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
