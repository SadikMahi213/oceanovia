<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Password reset links via email are currently unavailable.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-4">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    @endif
</x-guest-layout>
