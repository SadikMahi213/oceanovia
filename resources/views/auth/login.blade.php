@php $role = request('role', request()->query('role')) ?? old('role'); $role = in_array($role, ['customer','seller','supplier']) ? $role : null; $roleLabel = $role ? ucfirst($role) : null; @endphp
<x-guest-layout>
    @section('auth_title', $roleLabel ? $roleLabel.' Login' : 'Welcome back')
    @section('auth_subtitle', $roleLabel ? 'Sign in as '.$roleLabel.' to continue' : 'Sign in to your MulitVendor account')
    @section('auth_footer')
        <span>Don't have an account? <a href="{{ route('register', $role ? ['role'=>$role] : []) }}" class="font-medium text-market-600 hover:text-market-700 dark:text-market-400 dark:hover:text-market-300 transition-colors">Create one{{ $roleLabel ? ' as '.$roleLabel : '' }}</a></span>
    @endsection

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Role Switcher --}}
    <div class="flex items-center justify-center gap-1.5 mb-5 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
        @foreach(['customer'=>'Customer','seller'=>'Seller','supplier'=>'Supplier'] as $r => $label)
            <a href="{{ route('login', ['role'=>$r]) }}" class="flex-1 text-center px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $role===$r ? 'bg-white dark:bg-gray-700 text-market-600 dark:text-market-400 shadow-sm border border-gray-200 dark:border-gray-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </div>
    @if($role)
        <div class="mb-4 flex items-center gap-2 px-3 py-2.5 bg-market-50 dark:bg-market-900/20 border border-market-200 dark:border-market-800 rounded-xl">
            <div class="w-8 h-8 bg-market-600 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-market-700 dark:text-market-300">{{ $roleLabel }} Login</p>
                <p class="text-xs text-market-600 dark:text-market-400">You are signing in as {{ $roleLabel }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
            <x-text-input id="email" class="block mt-1.5 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 dark:focus:ring-market-400/10 outline-none transition-all" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div x-data="{ showPassword: false }">
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-gray-700 dark:text-gray-300" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-market-600 hover:text-market-700 dark:text-market-400 dark:hover:text-market-300 transition-colors">
                        {{ __('Forgot?') }}
                    </a>
                @endif
            </div>
            <div class="relative mt-1.5">
                <x-text-input id="password" class="block w-full px-4 py-2.5 pr-11 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 focus:ring-2 focus:ring-market-500/10 dark:focus:ring-market-400/10 outline-none transition-all"
                                type="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password"
                                placeholder="••••••••" />
                <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" :title="showPassword ? 'Hide password' : 'Show password'" tabindex="-1"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12z"/></svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 012.93-4.292M6.455 6.455A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.743 5.53M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/></svg>
                </button>
            </div>
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
                <span class="bg-white dark:bg-gray-900 px-3 text-gray-500 dark:text-gray-400">Secure checkout via MulitVendor</span>
            </div>
        </div>

        <p class="text-xs text-center text-gray-500 dark:text-gray-400">
            By continuing, you agree to our <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-300">Terms of Service</a> and <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-300">Privacy Policy</a>.
        </p>
    </form>
</x-guest-layout>
