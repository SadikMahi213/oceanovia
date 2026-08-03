<x-app-layout>
    @section('title', 'Page Not Found')
    <section class="min-h-[60vh] flex items-center justify-center bg-white dark:bg-gray-950">
        <div class="text-center max-w-md mx-auto px-4">
            <div class="w-20 h-20 mx-auto mb-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl flex items-center justify-center">
                <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-3">404</h1>
            <p class="text-lg text-gray-500 dark:text-gray-400 mb-6">The page you're looking for doesn't exist.</p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">Go Home</a>
        </div>
    </section>
</x-app-layout>
