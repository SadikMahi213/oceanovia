<x-app-layout>
    @section('title', 'Security')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Security</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your account security settings</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Account Information</h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Email</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                                <div>
                                    @if($user->hasVerifiedEmail())
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs font-medium rounded-full">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                            Unverified
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Phone</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->phone ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Member Since</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->created_at->format('F d, Y') }}</p>
                                </div>
                            </div>
                            @if($user->last_login_at)
                                <div class="flex items-center justify-between py-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Last Login</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->last_login_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                    @if($user->last_login_ip)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">IP: {{ $user->last_login_ip }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(!$user->hasVerifiedEmail())
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Email Verification</h2>
                            </div>
                            <div class="p-5">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Your email address is not verified. Please check your inbox for the verification link.</p>
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        Resend Verification Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-red-100 dark:border-red-900/30 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-red-100 dark:border-red-900/30">
                            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400">Delete Account</h2>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Once your account is deleted, all of your data will be permanently removed. This action cannot be undone.</p>
                            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account? This action is irreversible.')">
                                @csrf
                                @method('DELETE')
                                <label for="delete_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Enter your password to confirm</label>
                                <div class="flex items-center gap-3">
                                    <input type="password" id="delete_password" name="password" required placeholder="Your password"
                                        class="block w-full max-w-xs rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        Delete Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
