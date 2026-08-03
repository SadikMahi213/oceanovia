<x-app-layout>
    @section('title', 'Recently Viewed')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Recently Viewed</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Products you've recently browsed</p>
                        </div>
                    </div>

                    @if($items && $items->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                            @foreach($items as $item)
                                @include('components.product-card', ['product' => $item->product])
                            @endforeach
                        </div>
                        @if($items->hasPages())
                            <div class="mt-6">
                                {{ $items->links() }}
                            </div>
                        @endif
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No recently viewed items</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Products you browse will appear here</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
