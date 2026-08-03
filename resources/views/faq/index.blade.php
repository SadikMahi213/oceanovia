<x-app-layout>
    @section('title', 'FAQ')

    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">FAQ</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">Frequently Asked Questions</h1>
            <p class="text-market-200 mt-2 max-w-xl">Answers to common questions about shopping, selling, and shipping on MulitVendor USA.</p>
        </div>
    </section>

    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-4xl mx-auto px-4">
            @if($faqs->isEmpty())
                <div class="text-center py-16">
                    <p class="text-gray-500 dark:text-gray-400">No FAQs published yet. Please check back soon.</p>
                </div>
            @else
                @foreach($categories as $category)
                    @if($faqs->has($category))
                        <div class="mb-10" x-data="{ open: null }">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white capitalize mb-4">{{ $category }}</h2>
                            <div class="space-y-3">
                                @foreach($faqs->get($category) as $faq)
                                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
                                        <button type="button" @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                                            class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $faq->question }}</span>
                                            <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="open === {{ $faq->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open === {{ $faq->id }}" x-collapse>
                                            <div class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $faq->answer }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="mt-12 p-6 bg-market-50 dark:bg-gray-800 border border-market-100 dark:border-gray-700 rounded-2xl text-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Still have questions?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Our support team is happy to help.</p>
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Contact Us</a>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
