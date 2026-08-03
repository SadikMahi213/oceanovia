<x-app-layout>
    @section('title', $category->name)
    @section('meta_description', Str::limit(strip_tags($category->description ?? ''), 160))

    {{-- Category Hero --}}
    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNCI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyNHYyYTEwIDEwIDAgMCAxLTEyIDB2LTJoMTJ6TTM2IDM0djJhMTAgMTAgMCAwIDEtMTIgMHYtMmgxMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('categories.index') }}" class="hover:text-white transition-colors">Categories</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">{{ $category->name }}</span>
            </nav>
            <div class="flex items-center gap-6">
                @if($category->image_url)
                    <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl overflow-hidden border-2 border-white/20 shrink-0">
                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy" class="w-full h-full object-cover">
                    </div>
                @endif
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-white">{{ $category->name }}</h1>
                    @if($category->description)
                        <p class="text-market-200 mt-2 max-w-xl">{{ $category->description }}</p>
                    @endif
                    <p class="text-market-200/70 text-sm mt-1">{{ $products->total() ?? 0 }} products</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Subcategories --}}
    @if($category->children && $category->children->count() > 0)
        <section class="bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <a href="{{ route('categories.show', $category->slug) }}" class="shrink-0 px-4 py-2 bg-market-600 text-white text-sm font-medium rounded-lg transition-colors">All</a>
                    @foreach($category->children as $child)
                        <a href="{{ route('categories.show', $child->slug) }}" class="shrink-0 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-market-50 dark:hover:bg-market-900/20 hover:text-market-600 dark:hover:text-market-400 transition-colors">{{ $child->name }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Products --}}
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span class="font-medium text-gray-900 dark:text-white">{{ $products->firstItem() ?? 0 }}</span>
                    to <span class="font-medium text-gray-900 dark:text-white">{{ $products->lastItem() ?? 0 }}</span>
                    of <span class="font-medium text-gray-900 dark:text-white">{{ $products->total() }}</span> products
                </p>
                <div class="flex items-center gap-2">
                    @php
                        $sortLabels = ['newest' => 'Newest', 'oldest' => 'Oldest', 'price_asc' => 'Price: Low-High', 'price_desc' => 'Price: High-Low', 'name_asc' => 'Name: A-Z', 'name_desc' => 'Name: Z-A'];
                        $currentSort = request('sort', 'newest');
                    @endphp
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h6M3 17h6m3-10h9M12 12h9m-9 5h9"/></svg>
                            {{ $sortLabels[$currentSort] ?? 'Sort' }}
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-52 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50" x-cloak>
                            @foreach($sortLabels as $key => $label)
                                <a href="{{ route('categories.show', ['slug' => $category->slug, 'sort' => $key]) }}" class="block px-4 py-2 text-sm {{ $currentSort === $key ? 'text-market-600 dark:text-market-400 font-medium bg-market-50 dark:bg-market-900/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} transition-colors">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @if($products->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                    @foreach($products as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>
                <div class="mt-10">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-16 lg:py-24">
                    <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No products in this category yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Check back later or browse other categories.</p>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors">
                        Browse All Products
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
