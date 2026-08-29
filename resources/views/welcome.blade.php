@php
    $heroBanners = (isset($banners) && $banners instanceof \Illuminate\Support\Collection) ? $banners : collect([]);
    if ($heroBanners->isEmpty()) {
        $heroBanners = collect([
            (object)[
                'title' => 'Discover Unique\nAmerican-Made Products',
                'subtitle' => 'Shop from thousands of independent sellers and suppliers across the USA',
                'btn_text' => 'Start Shopping',
                'btn_link' => route('products.index'),
                'bg_gradient' => 'from-market-600 via-market-700 to-purple-900',
                'image_icon' => 'M',
            ],
        ]);
    }

    $testimonials = [
        (object)['name' => 'Sarah J.', 'role' => 'Verified Buyer', 'text' => 'I love supporting American small businesses. The quality is unmatched and shipping is always fast!', 'rating' => 5],
        (object)['name' => 'Mike R.', 'role' => 'Verified Buyer', 'text' => 'Found a unique handmade gift that I could not find anywhere else. This marketplace is a gem.', 'rating' => 5],
        (object)['name' => 'Emily T.', 'role' => 'Verified Buyer', 'text' => 'The customer service here is incredible. Had an issue with my order and it was resolved within hours.', 'rating' => 5],
        (object)['name' => 'David L.', 'role' => 'Verified Buyer', 'text' => 'Great prices and the sellers are super responsive. Will definitely be shopping here again.', 'rating' => 4],
    ];
@endphp

<x-app-layout>
    @section('title', 'Home')

    {{-- Hero Banner Carousel --}}
    <section
        x-data="{
            active: 0,
            count: {{ $heroBanners->count() }},
            timer: null,
            next() { if (this.count > 1) this.active = (this.active + 1) % this.count; },
            prev() { if (this.count > 1) this.active = (this.active - 1 + this.count) % this.count; },
            go(i) { this.active = i; },
            start() {
                if (this.count > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    this.timer = setInterval(() => this.next(), 6000);
                }
            },
            stop() { clearInterval(this.timer); },
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        @focusin="stop()"
        @focusout="start()"
        class="relative overflow-hidden bg-market-700"
        aria-roledescription="carousel"
        aria-label="Featured banners"
    >
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyNHYyYTEwIDEwIDAgMCAxLTEyIDB2LTJoMTJ6TTM2IDM0djJhMTAgMTAgMCAwIDEtMTIgMHYtMmgxMnoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative">
            <div class="flex transition-transform duration-700 ease-in-out" :style="`transform: translateX(-${active * 100}%)`">
                @foreach($heroBanners as $banner)
                <div class="w-full flex-shrink-0 bg-market-700 bg-gradient-to-br {{ $banner->bg_gradient ?? 'from-market-600 via-market-700 to-purple-900' }}"
                     role="group" aria-roledescription="slide" aria-label="Banner {{ $loop->iteration }} of {{ $heroBanners->count() }}">
                    <div class="max-w-7xl mx-auto px-4 py-16 lg:py-24 relative">
                        <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-16">
                            <div class="flex-1 text-center lg:text-left">
                                <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 backdrop-blur-sm rounded-full text-white/90 text-xs font-medium mb-6">
                                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                    USA Marketplace — Free Shipping Over $50
                                </span>
                                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-4">
                                    {!! nl2br(e($banner->title ?? 'Discover Unique\nAmerican-Made Products')) !!}
                                </h1>
                                <p class="text-lg text-purple-200 max-w-xl mb-8">
                                    {{ $banner->subtitle ?? 'Shop from thousands of independent sellers and suppliers across the USA' }}
                                </p>
                                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                                    <a href="{{ $banner->link ?? route('products.index') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-white text-market-700 font-semibold rounded-xl hover:bg-gray-100 transition-all shadow-xl shadow-black/10">
                                        {{ $banner->btn_text ?? 'Start Shopping' }}
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </a>
                                    <a href="{{ route('products.sellers') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 border-2 border-white/20 text-white font-semibold rounded-xl hover:bg-white/10 transition-all">
                                        Browse Sellers
                                    </a>
                                </div>
                                <div class="flex items-center gap-8 mt-10 justify-center lg:justify-start">
                                    <div>
                                        <div class="text-2xl font-bold text-white">10K+</div>
                                        <div class="text-sm text-purple-200">Products</div>
                                    </div>
                                    <div class="w-px h-10 bg-white/20"></div>
                                    <div>
                                        <div class="text-2xl font-bold text-white">500+</div>
                                        <div class="text-sm text-purple-200">Sellers</div>
                                    </div>
                                    <div class="w-px h-10 bg-white/20"></div>
                                    <div>
                                        <div class="text-2xl font-bold text-white">50K+</div>
                                        <div class="text-sm text-purple-200">Customers</div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @if($banner->image ?? false)
                                    <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?? 'Marketplace' }}" loading="lazy" class="w-64 h-64 lg:w-80 lg:h-80 object-cover rounded-3xl">
                                @else
                                <div class="w-64 h-64 lg:w-80 lg:h-80 bg-white/5 backdrop-blur-sm rounded-3xl flex items-center justify-center border border-white/10">
                                    <span class="text-8xl font-black text-white/20">{{ $banner->image_icon ?? 'M' }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if($heroBanners->count() > 1)
        <button type="button" @click="prev()" aria-label="Previous banner"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 flex items-center justify-center rounded-full bg-white/15 backdrop-blur-sm text-white hover:bg-white/30 transition focus:outline-none focus:ring-2 focus:ring-white/60">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" @click="next()" aria-label="Next banner"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 flex items-center justify-center rounded-full bg-white/15 backdrop-blur-sm text-white hover:bg-white/30 transition focus:outline-none focus:ring-2 focus:ring-white/60">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2 bg-black/20 backdrop-blur-sm rounded-full px-3 py-2">
            @foreach($heroBanners as $b)
            <button type="button" @click="go({{ $loop->index }})"
                :aria-current="active === {{ $loop->index }} ? 'true' : 'false'"
                aria-label="Go to banner {{ $loop->iteration }}"
                class="w-2.5 h-2.5 rounded-full transition focus:outline-none focus:ring-2 focus:ring-white/60"
                :class="active === {{ $loop->index }} ? 'bg-white scale-110' : 'bg-white/40 hover:bg-white/70'"></button>
            @endforeach
        </div>
        @endif

        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                <path d="M0 60V30C240 0 480 0 720 30C960 60 1200 60 1440 30V60H0Z" fill="white" class="fill-white dark:fill-gray-950"/>
            </svg>
        </div>
    </section>

    {{-- Trust Badges Bar --}}
    <section class="py-6 bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-2"><svg class="w-5 h-5 text-market-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Verified Sellers</span>
                <span class="inline-flex items-center gap-2"><svg class="w-5 h-5 text-market-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg> Secure Checkout</span>
                <span class="inline-flex items-center gap-2"><svg class="w-5 h-5 text-market-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> 30-Day Returns</span>
                <span class="inline-flex items-center gap-2"><svg class="w-5 h-5 text-market-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Free Shipping*</span>
            </div>
        </div>
    </section>

    {{-- Featured Categories --}}
    <section class="py-12 lg:py-20 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">Shop by Category</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Find exactly what you're looking for</p>
                </div>
                <a href="{{ route('categories.index') }}" class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">View All <span>→</span></a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @forelse($categories as $cat)
                <a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="group flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-transparent hover:border-market-200 dark:hover:border-market-700 transition-all hover:shadow-lg hover:-translate-y-0.5">
                    @if($cat->image)
                        <img src="{{ asset('storage/' . $cat->image) }}" alt="{{ $cat->name }}" loading="lazy" class="w-12 h-12 object-cover rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    @else
                        <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <span class="text-lg font-bold text-market-600 dark:text-market-400">{{ substr($cat->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $cat->name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $cat->products_count ?? 0 }} items</span>
                </a>
                @empty
                    @for($i = 0; $i < 6; $i++)
                    <div class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl animate-pulse">
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg mb-3"></div>
                        <div class="h-4 w-20 bg-gray-200 dark:bg-gray-700 rounded mb-1"></div>
                        <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                    @endfor
                @endforelse
            </div>
        </div>
    </section>

    {{-- Featured Products --}}
    <section class="py-12 lg:py-20 bg-gray-50 dark:bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">Featured Products</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Curated picks from top sellers</p>
                </div>
                <a href="{{ route('products.index') }}" class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">View All <span>→</span></a>
            </div>
            @if($featuredProducts->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                    @foreach($featuredProducts as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                    @for($i = 0; $i < 8; $i++)
                        @include('components.product-card-skeleton')
                    @endfor
                </div>
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">Products will appear here once sellers add them.</p>
                    @auth
                        @if(Auth::user()->isSeller())
                            <a href="#" onclick="alert('Seller dashboard coming in next phase!')" class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-market-600 text-white rounded-xl hover:bg-market-700 transition-colors font-medium">Add Your First Product</a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </section>

    {{-- Promotion Banner --}}
    <section class="py-12 lg:py-16 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-market-500 via-market-600 to-purple-800 p-8 lg:p-12">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTQwIDQwdjI4SDI4VjQwaDEyek00MCAyNHYyYTEwIDEwIDAgMCAwLTEyIDB2LTIgaDEyeiIvPjwvZz48L2c+PC9zdmc+')] opacity-50"></div>
                <div class="relative flex flex-col lg:flex-row items-center justify-between gap-6">
                    <div>
                        <span class="inline-block px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full text-white/80 text-xs font-medium mb-4">🔥 Limited Time</span>
                        <h2 class="text-2xl lg:text-3xl font-bold text-white mb-2">Start Selling on Oceanovia.com Today</h2>
                        <p class="text-purple-200">Join 500+ American sellers and reach thousands of customers nationwide.</p>
                    </div>
                    <a href="{{ route('register', ['role'=>'seller']) }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-white text-market-700 font-semibold rounded-xl hover:bg-gray-100 transition-all shadow-xl shadow-black/10 shrink-0">Become a Seller <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
                </div>
            </div>
        </div>
    </section>

    {{-- Why Choose Us (consistent market-* palette) --}}
    <section class="py-12 lg:py-20 bg-gray-50 dark:bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">Why Oceanovia.com?</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2">The best marketplace experience for everyone</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-market-200 dark:hover:border-market-700 transition-all group">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:bg-market-200 dark:group-hover:bg-market-900/50 transition-colors">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Free Shipping</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Free shipping on all orders over $50. Fast delivery across the USA.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-market-200 dark:hover:border-market-700 transition-all group">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:bg-market-200 dark:group-hover:bg-market-900/50 transition-colors">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Secure Payments</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Cash on Delivery. Pay only when you receive your order.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-market-200 dark:hover:border-market-700 transition-all group">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:bg-market-200 dark:group-hover:bg-market-900/50 transition-colors">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Easy Returns</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">30-day return policy. No questions asked. We've got you covered.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-market-200 dark:hover:border-market-700 transition-all group">
                    <div class="w-12 h-12 bg-market-100 dark:bg-market-900/30 rounded-xl flex items-center justify-center mb-4 group-hover:bg-market-200 dark:group-hover:bg-market-900/50 transition-colors">
                        <svg class="w-6 h-6 text-market-600 dark:text-market-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">USA Sellers Only</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Support American businesses. Every purchase helps local sellers thrive.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Customer Testimonials --}}
    <section class="py-12 lg:py-20 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">What Our Customers Say</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Join thousands of happy shoppers</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($testimonials as $t)
                    <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all">
                        <div class="flex items-center gap-1 mb-3">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-4 h-4 {{ $i < $t->rating ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">"{{ $t->text }}"</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-market-100 dark:bg-market-900/30 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-market-600 dark:text-market-400">{{ substr($t->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t->role }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

</x-app-layout>
