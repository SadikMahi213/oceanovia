<x-app-layout>
    @section('title', 'Categories')

    {{-- Hero --}}
    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNCI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyNHYyYTEwIDEwIDAgMCAxLTEyIDB2LTJoMTJ6TTM2IDM0djJhMTAgMTAgMCAwIDEtMTIgMHYtMmgxMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">Categories</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">Shop by Category</h1>
            <p class="text-market-200 mt-2 max-w-xl">Browse our curated collection from American sellers and suppliers.</p>
        </div>
    </section>

    {{-- Categories Grid --}}
    <section class="py-12 lg:py-20 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            @if($categories->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($categories as $category)
                        <a href="{{ route('categories.show', $category->slug) }}" class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            <div class="relative aspect-[16/9] bg-gray-50 dark:bg-gray-700 overflow-hidden">
                                @if($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-market-600 dark:group-hover:text-market-400 transition-colors">{{ $category->name }}</h3>
                                    <span class="text-sm font-medium text-market-600 dark:text-market-400">{{ $category->products_count ?? 0 }}</span>
                                </div>
                                @if($category->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $category->description }}</p>
                                @endif
                                @if($category->children && $category->children->count() > 0)
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach($category->children->take(3) as $child)
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs rounded-md">{{ $child->name }}</span>
                                        @endforeach
                                        @if($category->children->count() > 3)
                                            <span class="text-xs text-gray-400">+{{ $category->children->count() - 3 }} more</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 lg:py-24">
                    <svg class="w-20 h-20 mx-auto text-gray-200 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No categories yet</h3>
                    <p class="text-gray-500 dark:text-gray-400">Categories will appear here once they are created.</p>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
