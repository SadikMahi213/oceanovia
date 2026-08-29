@php $role = request('role', request()->query('role')) ?? old('role_type'); $role = in_array($role, ['customer','seller','supplier']) ? $role : null; $roleLabel = $role ? ucfirst($role) : null; @endphp
<x-guest-layout>
    @section('auth_title', $roleLabel ? 'Create '.$roleLabel.' Account' : 'Create your account')
    @section('auth_subtitle', $roleLabel ? 'You\'re registering as '.$roleLabel.' — join as '.$roleLabel : 'Join Oceanovia.com and start shopping or selling today')
    @section('auth_footer')
        <span>Already have an account? <a href="{{ route('login', $role ? ['role'=>$role] : []) }}" class="font-medium text-market-600 hover:text-market-700 dark:text-market-400 dark:hover:text-market-300 transition-colors">Sign in{{ $roleLabel ? ' as '.$roleLabel : '' }}</a></span>
    @endsection

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        {{-- Role Switcher --}}
        <div class="flex items-center justify-center gap-1.5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
            @foreach(['customer'=>'Customer','seller'=>'Seller','supplier'=>'Supplier'] as $r => $label)
                <a href="{{ route('register', ['role'=>$r]) }}" class="flex-1 text-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ ($role ?? 'customer')===$r ? 'bg-white dark:bg-gray-700 text-market-600 dark:text-market-400 shadow-sm border border-gray-200 dark:border-gray-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if($role)
            <div class="flex items-center gap-2 px-3 py-2.5 bg-market-50 dark:bg-market-900/20 border border-market-200 dark:border-market-800 rounded-xl">
                <div class="w-8 h-8 bg-market-600 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-market-700 dark:text-market-300">Create {{ $roleLabel }} Account</p>
                    <p class="text-xs text-market-600 dark:text-market-400">You’re registering as {{ $roleLabel }}</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('First Name')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
                <x-text-input id="name" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 outline-none transition-all" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="John" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>

            <!-- Last Name -->
            <div>
                <x-input-label for="lastname" :value="__('Last Name')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
                <x-text-input id="lastname" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 outline-none transition-all" type="text" name="lastname" :value="old('lastname')" autocomplete="family-name" placeholder="Doe" />
                <x-input-error :messages="$errors->get('lastname')" class="mt-1.5" />
            </div>
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <x-text-input id="email" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 outline-none transition-all" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('Phone (optional)')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <x-text-input id="phone" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 outline-none transition-all" type="text" name="phone" :value="old('phone')" placeholder="+1 (555) 000-0000" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
        </div>

        <!-- Role Selection -->
        <div>
            <x-input-label :value="__('I want to join as')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <div class="mt-2 grid grid-cols-3 gap-2">
                <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 has-[:checked]:border-market-500 has-[:checked]:bg-market-50 dark:has-[:checked]:bg-market-900/20 has-[:checked]:ring-2 has-[:checked]:ring-market-500/20 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600">
                    <input type="radio" name="role_type" value="customer" class="sr-only" {{ old('role_type', $role ?? 'customer') === 'customer' ? 'checked' : '' }} required>
                    <div class="w-full text-center">
                        <svg class="mx-auto h-6 w-6 text-gray-400 dark:text-gray-500 has-[:checked]:text-market-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        <span class="mt-1 block text-xs font-semibold text-gray-900 dark:text-white">Customer</span>
                        <span class="block text-[10px] text-gray-400 dark:text-gray-500">Shop only</span>
                    </div>
                </label>

                <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 has-[:checked]:border-market-500 has-[:checked]:bg-market-50 dark:has-[:checked]:bg-market-900/20 has-[:checked]:ring-2 has-[:checked]:ring-market-500/20 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600">
                    <input type="radio" name="role_type" value="seller" class="sr-only" {{ old('role_type', $role) === 'seller' ? 'checked' : '' }} required>
                    <div class="w-full text-center">
                        <svg class="mx-auto h-6 w-6 text-gray-400 dark:text-gray-500 has-[:checked]:text-market-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/></svg>
                        <span class="mt-1 block text-xs font-semibold text-gray-900 dark:text-white">Seller</span>
                        <span class="block text-[10px] text-gray-400 dark:text-gray-500">Sell products</span>
                    </div>
                </label>

                <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all duration-200 has-[:checked]:border-market-500 has-[:checked]:bg-market-50 dark:has-[:checked]:bg-market-900/20 has-[:checked]:ring-2 has-[:checked]:ring-market-500/20 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600">
                    <input type="radio" name="role_type" value="supplier" class="sr-only" {{ old('role_type', $role) === 'supplier' ? 'checked' : '' }} required>
                    <div class="w-full text-center">
                        <svg class="mx-auto h-6 w-6 text-gray-400 dark:text-gray-500 has-[:checked]:text-market-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        <span class="mt-1 block text-xs font-semibold text-gray-900 dark:text-white">Supplier</span>
                        <span class="block text-[10px] text-gray-400 dark:text-gray-500">Wholesale</span>
                    </div>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role_type')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <x-text-input id="password" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 outline-none transition-all"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <x-text-input id="password_confirmation" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 outline-none transition-all"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password"
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <button type="submit" class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-market-500/10">
            {{ __('Create account') }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </button>

        <p class="text-xs text-center text-gray-400 dark:text-gray-500 mt-4">
            By creating an account, you agree to our <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-300">Terms of Service</a> and <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-300">Privacy Policy</a>.
        </p>
    </form>
</x-guest-layout>
