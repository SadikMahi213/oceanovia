<x-app-layout>
    @section('title', 'Server Error')
    <section class="min-h-[60vh] flex items-center justify-center bg-white dark:bg-gray-950">
        <div class="text-center max-w-md mx-auto px-4">
            <div class="w-20 h-20 mx-auto mb-6 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-3">500</h1>
            <p class="text-lg text-gray-500 dark:text-gray-400 mb-6">Something went wrong on our end. Please try again later.</p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">Go Home</a>
        </div>
    </section>
</x-app-layout>
