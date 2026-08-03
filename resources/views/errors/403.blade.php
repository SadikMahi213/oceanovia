<x-app-layout>
    @section('title', 'Forbidden')
    <section class="min-h-[60vh] flex items-center justify-center bg-white dark:bg-gray-950">
        <div class="text-center max-w-md mx-auto px-4">
            <div class="w-20 h-20 mx-auto mb-6 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9.364-7.364A9 9 0 1112 3a9 9 0 017.364 4.636z"/></svg>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-3">403</h1>
            <p class="text-lg text-gray-500 dark:text-gray-400 mb-6">You don't have permission to access this page.</p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">Go Home</a>
        </div>
    </section>
</x-app-layout>
