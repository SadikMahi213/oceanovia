<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data>
<head>
    <meta charset="utf-8">
    <script>
        (function () {
            try {
                if (localStorage.getItem('darkMode') === 'true') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authed" content="{{ auth()->check() ? '1' : '0' }}">

    {{-- SEO Meta --}}
    <title>@hasSection('title') @yield('title') | @endif {{ config('app.name', 'MulitVendor USA') }}</title>
    <meta name="description" content="@yield('meta_description', 'MulitVendor USA - The premier marketplace for unique products from American sellers and suppliers.')">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'Discover amazing products from sellers across the USA.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.png'))">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200">

{{-- Toast Notifications Container --}}
<div class="fixed top-4 right-4 z-[9999] space-y-3 pointer-events-none" x-data x-cloak>
    <template x-for="notif in $store.toast.notifications" :key="notif.id">
        <div class="pointer-events-auto flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg border animate-toast-in"
             :class="{
                 'bg-green-50 border-green-200 text-green-800': notif.type === 'success',
                 'bg-red-50 border-red-200 text-red-800': notif.type === 'error',
                 'bg-blue-50 border-blue-200 text-blue-800': notif.type === 'info',
                 'bg-yellow-50 border-yellow-200 text-yellow-800': notif.type === 'warning',
             }">
            <span x-text="notif.type === 'success' ? '✓' : notif.type === 'error' ? '✕' : notif.type === 'info' ? 'ℹ' : '⚠'" class="text-lg font-bold"></span>
            <span class="text-sm font-medium" x-text="notif.message"></span>
        </div>
    </template>
</div>

{{-- Quick Action Bar (Mobile floating cart & wishlist) --}}
<div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 sm:hidden">
    <a href="{{ route('cart.index') }}" class="flex items-center justify-center w-12 h-12 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 relative">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
        <span class="absolute -top-1 -right-1 bg-market-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-text="$store.cart.count" x-show="$store.cart.count > 0" x-cloak></span>
    </a>
</div>

{{-- Header / Navigation --}}
@include('layouts.navigation')

{{-- Site Announcements --}}
<x-announcements />

{{-- Main Content --}}
<main>
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-2xl px-5 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-2xl px-5 py-3 text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        </div>
    @endif
    {{ $slot }}
</main>

{{-- Footer --}}
@include('layouts.footer')

{{-- Scripts --}}
@stack('scripts')

</body>
</html>
