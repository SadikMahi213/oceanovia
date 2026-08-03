<x-guest-layout>
    @section('auth_title', 'Welcome back')
    @section('auth_subtitle', 'Sign in to your MulitVendor account')
    @section('auth_footer')
        <span>Don't have an account? <a href="{{ route('register') }}" class="font-medium text-market-600 hover:text-market-700 dark:text-market-400 dark:hover:text-market-300 transition-colors">Create one</a></span>
    @endsection

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <x-text-input id="email" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 dark:focus:ring-market-400/10 outline-none transition-all" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-market-600 hover:text-market-700 dark:text-market-400 dark:hover:text-market-300 transition-colors">
                        {{ __('Forgot?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 dark:focus:ring-market-400/10 outline-none transition-all"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div>
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded-lg border-gray-300 dark:border-gray-700 text-market-600 focus:ring-market-500 dark:bg-gray-800" name="remember">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="space-y-3">
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-market-500/10">
                {{ __('Sign in') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </button>

            <a href="{{ route('register') }}" class="w-full flex items-center justify-center gap-2 px-5 py-3 border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">
                {{ __('Create an account') }}
            </a>
        </div>

        {{-- Social proof --}}
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white dark:bg-gray-900 px-3 text-gray-400 dark:text-gray-500">Secure checkout via MulitVendor</span>
            </div>
        </div>

        <p class="text-xs text-center text-gray-400 dark:text-gray-500">
            By continuing, you agree to our <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-300">Terms of Service</a> and <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-300">Privacy Policy</a>.
        </p>
    </form>
</x-guest-layout>
