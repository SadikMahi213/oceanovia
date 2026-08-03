<x-app-layout>
    @section('title', $page->title)

    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">{{ $page->title }}</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">{{ $page->title }}</h1>
            @if($page->meta_description)
                <p class="text-market-200 mt-2 max-w-xl">{{ $page->meta_description }}</p>
            @endif
        </div>
    </section>

    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-4xl mx-auto px-4">
            <article class="prose prose-lg prose-market dark:prose-invert max-w-none">
                {!! $page->content !!}
            </article>
        </div>
    </section>
</x-app-layout>
