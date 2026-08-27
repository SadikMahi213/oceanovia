@php
    $selectedCategory = request('category', '');
    $sort = request('sort', 'newest');
    $minPrice = request('min_price', '');
    $maxPrice = request('max_price', '');
@endphp

<x-app-layout>
    @section('title', 'All Products')

    @push('styles')
    <style>
        .price-range::-webkit-slider-thumb { appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #0d9488; cursor: pointer; }
        .price-range::-moz-range-thumb { width: 18px; height: 18px; border-radius: 50%; background: #0d9488; cursor: pointer; border: none; }
    </style>
    @endpush

    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNCI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyNHYyYTEwIDEwIDAgMCAxLTEyIDB2LTJoMTJ6TTM2IDM0djJhMTAgMTAgMCAwIDEtMTIgMHYtMmgxMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">Products</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">All Products</h1>
            <p class="text-market-200 mt-2 max-w-xl">Browse our curated collection from American sellers and suppliers.</p>
        </div>
    </section>

    {{-- Main Content --}}
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                {{-- Sidebar Filters --}}
                <aside class="hidden lg:block w-64 shrink-0">
                    <div class="sticky top-28 space-y-6">
                        {{-- Categories --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Categories</h3>
                            <div class="space-y-2">
                                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => ''])) }}" class="flex items-center justify-between text-sm {{ $selectedCategory === '' ? 'text-market-600 dark:text-market-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-market-600 dark:hover:text-market-400' }} transition-colors">
                                    <span>All Categories</span>
                                </a>
                                @foreach($categories as $category)
                                    <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => $category->slug])) }}" class="flex items-center justify-between text-sm {{ $selectedCategory === $category->slug ? 'text-market-600 dark:text-market-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-market-600 dark:hover:text-market-400' }} transition-colors">
                                        <span>{{ $category->name }}</span>
                                        @if(isset($category->products_count))
                                            <span class="text-xs text-gray-500">({{ $category->products_count }})</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Price Range --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Price Range</h3>
                            <form action="{{ route('products.index') }}" method="GET">
                                @foreach(request()->except('min_price', 'max_price', 'page') as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <div class="space-y-4">
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="min_price" placeholder="Min" value="{{ $minPrice }}" min="0" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                        <span class="text-gray-400">—</span>
                                        <input type="number" name="max_price" placeholder="Max" value="{{ $maxPrice }}" min="0" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                                    </div>
                                    <button type="submit" class="w-full py-2 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-lg transition-colors">Filter</button>
                                </div>
                            </form>
                        </div>

                        {{-- Sort by --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Sort By</h3>
                            <div class="space-y-2">
                                @php
                                    $sortOptions = [
                                        'newest' => 'Newest',
                                        'oldest' => 'Oldest',
                                        'price_asc' => 'Price: Low to High',
                                        'price_desc' => 'Price: High to Low',
                                        'name_asc' => 'Name: A-Z',
                                        'name_desc' => 'Name: Z-A',
                                        'popular' => 'Most Popular',
                                        'rating' => 'Highest Rated',
                                    ];
                                @endphp
                                @foreach($sortOptions as $key => $label)
                                    <a href="{{ route('products.index', array_merge(request()->except('sort', 'page'), ['sort' => $key])) }}" class="block text-sm {{ $sort === $key ? 'text-market-600 dark:text-market-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-market-600 dark:hover:text-market-400' }} transition-colors">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Product Grid --}}
                <div class="flex-1 min-w-0">
                    {{-- Mobile filter bar + results count --}}
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Showing <span class="font-medium text-gray-900 dark:text-white">{{ $products->firstItem() ?? 0 }}</span>
                                to <span class="font-medium text-gray-900 dark:text-white">{{ $products->lastItem() ?? 0 }}</span>
                                of <span class="font-medium text-gray-900 dark:text-white">{{ $products->total() }}</span> results
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            {{-- Mobile sort trigger --}}
                            <div class="lg:hidden" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h6M3 17h6m3-10h9M12 12h9m-9 5h9"/></svg>
                                    Sort
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-4 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50" x-cloak>
                                    @foreach($sortOptions as $key => $label)
                                        <a href="{{ route('products.index', array_merge(request()->except('sort', 'page'), ['sort' => $key])) }}" class="block px-4 py-2 text-sm {{ $sort === $key ? 'text-market-600 dark:text-market-400 font-medium bg-market-50 dark:bg-market-900/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} transition-colors">
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Mobile filter button --}}
                            <button x-data @click="$dispatch('open-mobile-filters')" class="lg:hidden flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                Filters
                            </button>
                        </div>
                    </div>

                    {{-- Active Filters --}}
                    @if($selectedCategory || $minPrice || $maxPrice)
                        <div class="flex items-center flex-wrap gap-2 mb-4">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Active filters:</span>
                            @if($selectedCategory)
                                @php $catName = $categories->firstWhere('slug', $selectedCategory)?->name ?? $selectedCategory; @endphp
                                <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => ''])) }}" class="inline-flex items-center gap-1 px-3 py-1 bg-market-50 dark:bg-market-900/20 text-market-700 dark:text-market-300 text-xs font-medium rounded-lg hover:bg-market-100 dark:hover:bg-market-900/40 transition-colors">
                                    {{ $catName }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </a>
                            @endif
                            @if($minPrice || $maxPrice)
                                <a href="{{ route('products.index', array_merge(request()->except('min_price', 'max_price', 'page'))) }}" class="inline-flex items-center gap-1 px-3 py-1 bg-market-50 dark:bg-market-900/20 text-market-700 dark:text-market-300 text-xs font-medium rounded-lg hover:bg-market-100 dark:hover:bg-market-900/40 transition-colors">
                                    ${{ $minPrice ?: '0' }} – ${{ $maxPrice ?: '∞' }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Products --}}
                    @if($products->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6">
                            @foreach($products as $product)
                                @include('components.product-card', ['product' => $product])
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-10">
                            {{ $products->appends(request()->query())->links() }}
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="text-center py-16 lg:py-24">
                            <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No products found</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">We couldn't find any products matching your criteria. Try adjusting your filters or browse all products.</p>
                            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">
                                Clear All Filters
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Filters Drawer --}}
    <div x-data="{ open: false }" x-on:open-mobile-filters.window="open = true" x-show="open" class="fixed inset-0 z-[100] lg:hidden" x-cloak>
        <div x-show="open" @click="open = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute right-0 top-0 bottom-0 w-80 max-w-[85vw] bg-white dark:bg-gray-900 shadow-2xl overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 px-5 py-4 flex items-center justify-between z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h3>
                <button @click="open = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-6">
                {{-- Categories --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Categories</h4>
                    <div class="space-y-2">
                        <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => ''])) }}" class="block text-sm {{ $selectedCategory === '' ? 'text-market-600 dark:text-market-400 font-medium' : 'text-gray-600 dark:text-gray-400' }} transition-colors">All Categories</a>
                        @foreach($categories as $category)
                            <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => $category->slug])) }}" class="block text-sm {{ $selectedCategory === $category->slug ? 'text-market-600 dark:text-market-400 font-medium' : 'text-gray-600 dark:text-gray-400' }} transition-colors">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>
                {{-- Price Range --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Price Range</h4>
                    <form action="{{ route('products.index') }}" method="GET" class="space-y-3">
                        @foreach(request()->except('min_price', 'max_price', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <div class="flex items-center gap-2">
                            <input type="number" name="min_price" placeholder="Min" value="{{ $minPrice }}" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm focus:border-market-500 outline-none">
                            <span class="text-gray-400">—</span>
                            <input type="number" name="max_price" placeholder="Max" value="{{ $maxPrice }}" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm focus:border-market-500 outline-none">
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-lg transition-colors">Apply Price</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
