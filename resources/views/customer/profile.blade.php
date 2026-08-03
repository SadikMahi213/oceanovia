<x-app-layout>
    @section('title', 'My Profile')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your personal information</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Personal Information</h2>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="name" value="First Name" />
                                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="lastname" value="Last Name" />
                                        <input type="text" id="lastname" name="lastname" value="{{ old('lastname', $user->lastname) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('lastname')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="username" value="Username" />
                                        <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('username')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="email" value="Email" />
                                        <input type="email" id="email" value="{{ $user->email }}" readonly
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 shadow-sm text-sm cursor-not-allowed">
                                    </div>
                                    <div>
                                        <x-input-label for="phone" value="Phone" />
                                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="date_of_birth" value="Date of Birth" />
                                        <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="gender" value="Gender" />
                                        <select id="gender" name="gender"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Location</h2>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="country" value="Country" />
                                        <input type="text" id="country" name="country" value="{{ old('country', $user->country) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('country')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="city" value="City" />
                                        <input type="text" id="city" name="city" value="{{ old('city', $user->city) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('city')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="state" value="State" />
                                        <input type="text" id="state" name="state" value="{{ old('state', $user->state) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('state')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="postal_code" value="Postal Code" />
                                        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('postal_code')" class="mt-1" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Photos</h2>
                            </div>
                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="avatar" value="Avatar" />
                                        <input type="file" id="avatar" name="avatar" accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('avatar')" class="mt-1" />
                                        @if($user->avatar)
                                            <div class="mt-3">
                                                <img src="{{ $user->avatar }}" alt="Current avatar" class="w-20 h-20 rounded-full object-cover border border-gray-200 dark:border-gray-600">
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="cover_image" value="Cover Image" />
                                        <input type="file" id="cover_image" name="cover_image" accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('cover_image')" class="mt-1" />
                                        @if($user->cover_image)
                                            <div class="mt-3">
                                                <img src="{{ $user->cover_image }}" alt="Current cover" class="w-full h-24 rounded-xl object-cover border border-gray-200 dark:border-gray-600">
                                            </div>
                                        @endif
                                    </div>
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
