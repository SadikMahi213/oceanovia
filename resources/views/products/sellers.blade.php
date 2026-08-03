<x-app-layout>
    @section('title', 'Featured Sellers')

    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNCI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyNHYyYTEwIDEwIDAgMCAxLTEyIDB2LTJoMTJ6TTM2IDM0djJhMTAgMTAgMCAwIDEtMTIgMHYtMmgxMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">Featured Sellers</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">Featured Sellers</h1>
            <p class="text-market-200 mt-2 max-w-xl">Discover amazing products from American sellers across the country.</p>
        </div>
    </section>

    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            @if($sellers->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($sellers as $seller)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all">
                            @if($seller->sellerProfile?->store_banner)
                                <div class="h-32 bg-cover bg-center" style="background-image: url('{{ asset('storage/' . $seller->sellerProfile->store_banner) }}')"></div>
                            @else
                                <div class="h-32 bg-gradient-to-r from-market-500 to-purple-600"></div>
                            @endif
                            <div class="p-6 -mt-12">
                                <div class="flex items-end gap-4 mb-4">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden border-4 border-white dark:border-gray-800 bg-white dark:bg-gray-700">
                                        @if($seller->sellerProfile?->store_logo)
                                            <img src="{{ asset('storage/' . $seller->sellerProfile->store_logo) }}" alt="{{ $seller->sellerProfile->store_name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-market-100 dark:bg-market-900/30">
                                                <span class="text-xl font-bold text-market-600 dark:text-market-400">{{ substr($seller->sellerProfile?->store_name ?? $seller->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $seller->sellerProfile?->store_name ?? $seller->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $seller->sellerProfile?->description }}</p>
                                <div class="flex items-center gap-4 mt-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $seller->products_count ?? 0 }} products</span>
                                </div>
                                <a href="{{ route('products.index', ['seller' => $seller->id]) }}" class="mt-4 w-full inline-flex items-center justify-center gap-2 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    View Store
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-10">
                    {{ $sellers->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No sellers yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Be the first to join our marketplace!</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">
                        Become a Seller
                    </a>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
