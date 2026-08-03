<x-app-layout>
    @section('title', 'My Wishlist')

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-10 lg:py-14">
        <div class="max-w-7xl mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-3">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">Wishlist</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">My Wishlist</h1>
            <p class="text-market-200 mt-1" x-text="`${$store.wishlist.count} saved item${$store.wishlist.count !== 1 ? 's' : ''}`"></p>
        </div>
    </section>

    {{-- Products Grid --}}
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950" x-data>
        <div class="max-w-7xl mx-auto px-4">
            @if($products && $products->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                    @foreach($products as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-16 lg:py-24">
                    <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Your wishlist is empty</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">Save your favorite items by clicking the heart icon on any product. They'll appear here for easy access!</p>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-market-600/20">
                        Discover Products
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
