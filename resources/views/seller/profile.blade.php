<x-app-layout>
    @section('title', 'Seller Profile')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="$profile?->store_name" :storeLogo="$profile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Store Profile</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your store information and branding</p>
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

                    <form method="POST" action="{{ route('seller.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Branding</h2>
                            </div>
                            <div class="p-5 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="store_logo" value="Store Logo" />
                                        <input type="file" id="store_logo" name="store_logo" accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('store_logo')" class="mt-1" />
                                        @if($profile?->logo_url)
                                            <div class="mt-3">
                                                <img src="{{ $profile->logo_url }}" alt="Current logo" class="w-20 h-20 rounded-xl object-cover border border-gray-200 dark:border-gray-600">
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="store_banner" value="Store Banner" />
                                        <input type="file" id="store_banner" name="store_banner" accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('store_banner')" class="mt-1" />
                                        @if($profile?->banner_url)
                                            <div class="mt-3">
                                                <img src="{{ $profile->banner_url }}" alt="Current banner" class="w-full h-24 rounded-xl object-cover border border-gray-200 dark:border-gray-600">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Store Information</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="store_name" value="Store Name *" />
                                        <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $profile?->store_name) }}" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('store_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="store_slug" value="Store Slug" />
                                        <input type="text" id="store_slug" name="store_slug" value="{{ old('store_slug', $profile?->store_slug) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">URL-friendly name (auto-generated if empty)</p>
                                        <x-input-error :messages="$errors->get('store_slug')" class="mt-1" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="description" value="Store Description" />
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('description', $profile?->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Contact Information</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="address" value="Address" />
                                    <textarea id="address" name="address" rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('address', $profile?->address) }}</textarea>
                                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="phone" value="Phone" />
                                    <input type="text" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="website" value="Website" />
                                    <input type="url" id="website" name="website" value="{{ old('website', $profile?->website) }}"
                                        placeholder="https://example.com"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('website')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Social Links</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="facebook" value="Facebook URL" />
                                    <input type="url" id="facebook" name="facebook" value="{{ old('facebook', $profile?->facebook) }}"
                                        placeholder="https://facebook.com/yourstore"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('facebook')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="instagram" value="Instagram URL" />
                                    <input type="url" id="instagram" name="instagram" value="{{ old('instagram', $profile?->instagram) }}"
                                        placeholder="https://instagram.com/yourstore"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('instagram')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="twitter" value="Twitter / X URL" />
                                    <input type="url" id="twitter" name="twitter" value="{{ old('twitter', $profile?->twitter) }}"
                                        placeholder="https://twitter.com/yourstore"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('twitter')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="youtube" value="YouTube URL" />
                                    <input type="url" id="youtube" name="youtube" value="{{ old('youtube', $profile?->youtube) }}"
                                        placeholder="https://youtube.com/@yourstore"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('youtube')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
