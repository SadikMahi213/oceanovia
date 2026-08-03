<x-app-layout>
    @section('title', 'Maintenance')
    <section class="min-h-[60vh] flex items-center justify-center bg-white dark:bg-gray-950">
        <div class="text-center max-w-md mx-auto px-4">
            <div class="w-20 h-20 mx-auto mb-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center">
                <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-3">Under Maintenance</h1>
            <p class="text-lg text-gray-500 dark:text-gray-400 mb-6">We're improving the site. Be right back!</p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">Try Again</a>
        </div>
    </section>
</x-app-layout>
