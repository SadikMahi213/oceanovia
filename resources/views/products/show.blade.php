@php
    $images = $product->images ?? [$product->thumbnail];
    $primaryImage = $images[0] ?? $product->thumbnail;
@endphp

<x-app-layout>
    @section('title', $product->name)
    @section('meta_description', Str::limit(strip_tags($product->short_description ?? $product->description), 160))

    {{-- Recently Viewed Tracking --}}
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            let recently = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
            recently = recently.filter(id => id !== {{ $product->id }});
            recently.unshift({{ $product->id }});
            if (recently.length > 20) recently = recently.slice(0, 20);
            localStorage.setItem('recentlyViewed', JSON.stringify(recently));
        });
    </script>
    @endpush

    {{-- Hero / Breadcrumb --}}
    <section class="bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('categories.index') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Categories</a>
                @if($product->category)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ route('categories.show', $product->category->slug) }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">{{ $product->category->name }}</a>
                @endif
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 dark:text-white font-medium truncate max-w-[200px]">{{ $product->name }}</span>
            </nav>
        </div>
    </section>

    {{-- Product Detail --}}
    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                {{-- Left: Image Gallery --}}
                <div>
                    <div class="relative aspect-square bg-gray-50 dark:bg-gray-800 rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 mb-4" x-data="{ selectedImage: '{{ $primaryImage }}' }">
                        <img :src="selectedImage" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @if($product->discount_percent)
                            <span class="absolute top-4 left-4 px-3 py-1 bg-red-500 text-white text-sm font-bold rounded-lg">-{{ $product->discount_percent }}%</span>
                        @endif
                        <button @click="$store.wishlist.toggle({{ $product->id }}); $store.toast.success($store.wishlist.has({{ $product->id }}) ? 'Added to wishlist' : 'Removed from wishlist')" class="absolute top-4 right-4 w-10 h-10 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white dark:hover:bg-gray-800 transition-all shadow-sm" :class="{ 'text-red-500': $store.wishlist.has({{ $product->id }}), 'text-gray-400': !$store.wishlist.has({{ $product->id }}) }">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>
                    </div>
                    @if(count($images) > 1)
                        <div class="flex gap-3 overflow-x-auto pb-2" x-data="{ selectedImage: '{{ $primaryImage }}' }">
                            @foreach($images as $image)
                                <button @click="selectedImage = '{{ $image }}'" class="w-20 h-20 shrink-0 rounded-xl overflow-hidden border-2 transition-colors" :class="selectedImage === '{{ $image }}' ? 'border-market-500 dark:border-market-400' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <img src="{{ $image }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Right: Product Info --}}
                <div>
                    {{-- Seller --}}
                    @if($product->seller)
                        <a href="{{ route('products.index', ['seller' => $product->seller->id]) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-market-50 dark:bg-market-900/20 rounded-lg mb-4 group">
                            <div class="w-6 h-6 bg-market-200 dark:bg-market-800 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-market-700 dark:text-market-300">{{ substr($product->seller->name, 0, 1) }}</span>
                            </div>
                            <span class="text-sm font-medium text-market-700 dark:text-market-300 group-hover:text-market-600 dark:group-hover:text-market-400 transition-colors">{{ $product->seller->name }}</span>
                        </a>
                    @endif

                    {{-- Title --}}
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-3">{{ $product->name }}</h1>

                    {{-- Rating --}}
                    @if(isset($product->reviews_count) && $product->reviews_count > 0)
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= round($product->rating_average ?? 0) ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($product->rating_average ?? 0, 1) }} ({{ $product->reviews_count }} reviews)</span>
                        </div>
                    @endif

                    {{-- Price --}}
                    <div class="flex items-baseline gap-3 mb-6">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($product->price, 2) }}</span>
                        @if($product->compare_price && $product->compare_price > $product->price)
                            <span class="text-xl text-gray-400 line-through">${{ number_format($product->compare_price, 2) }}</span>
                            <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold rounded-lg">{{ round((($product->compare_price - $product->price) / $product->compare_price) * 100) }}% OFF</span>
                        @endif
                    </div>

                    {{-- Short Description --}}
                    @if($product->short_description)
                        <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">{{ $product->short_description }}</p>
                    @endif

                    {{-- Attributes --}}
                    @php
                        $colors = $product->colors ?? [];
                        $sizes = $product->sizes ?? [];
                    @endphp
                    @if(!empty($colors))
                        <div class="mb-5">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Colors</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($colors as $color)
                                    <span class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm rounded-lg border border-gray-200 dark:border-gray-700">{{ $color }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(!empty($sizes))
                        <div class="mb-5">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Sizes</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($sizes as $size)
                                    <span class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm rounded-lg border border-gray-200 dark:border-gray-700">{{ $size }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- SKU, Category, Tags --}}
                    <div class="space-y-2 mb-6 text-sm text-gray-500 dark:text-gray-400">
                        @if($product->sku)
                            <p><span class="font-medium text-gray-700 dark:text-gray-300">SKU:</span> {{ $product->sku }}</p>
                        @endif
                        @if($product->category)
                            <p><span class="font-medium text-gray-700 dark:text-gray-300">Category:</span> <a href="{{ route('categories.show', $product->category->slug) }}" class="text-market-600 dark:text-market-400 hover:underline">{{ $product->category->name }}</a></p>
                        @endif
                        @if($product->tags && count($product->tags) > 0)
                            <p><span class="font-medium text-gray-700 dark:text-gray-300">Tags:</span>
                                @foreach($product->tags as $tag)
                                    <a href="{{ route('products.index', ['tag' => $tag]) }}" class="inline-block px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded-md hover:bg-market-50 dark:hover:bg-market-900/20 hover:text-market-600 dark:hover:text-market-400 transition-colors mr-1">{{ $tag }}</a>
                                @endforeach
                            </p>
                        @endif
                    </div>

                    {{-- Quantity + Add to Cart --}}
                    <div class="flex items-center gap-4 mb-8" x-data="{ qty: 1 }">
                        <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                            <button @click="qty = Math.max(1, qty - 1)" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <input type="number" x-model="qty" min="1" class="w-14 text-center text-sm font-medium bg-transparent border-x border-gray-200 dark:border-gray-700 py-2 outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <button @click="qty = Math.min(99, qty + 1)" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <button @click="for (let i = 0; i < qty; i++) $store.cart.addItem({{ Js::from(['id' => $product->id, 'name' => $product->name, 'price' => $product->price]) }}); $store.toast.success('Added to cart')" class="flex-1 py-3 bg-market-600 hover:bg-market-700 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            Add to Cart
                        </button>
                    </div>

                    {{-- Trust badges --}}
                    <div class="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-800 pt-6">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Free Shipping over $50
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            30-Day Returns
                        </span>
                    </div>
                </div>
            </div>

            {{-- Full Description --}}
            @if($product->description)
                <div class="mt-10 lg:mt-16 border-t border-gray-100 dark:border-gray-800 pt-8 lg:pt-12">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Description</h2>
                    <div class="prose prose-gray dark:prose-invert max-w-none text-gray-600 dark:text-gray-400 leading-relaxed">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>
            @endif

            {{-- Reviews Section --}}
            <div class="mt-10 lg:mt-16 border-t border-gray-100 dark:border-gray-800 pt-8 lg:pt-12" x-data="{ showForm: false }">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Customer Reviews</h2>
                    @auth
                        <button @click="showForm = !showForm" class="px-5 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                            Write a Review
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Sign in to Review</a>
                    @endauth
                </div>

                {{-- Rating Overview --}}
                @php
                    $totalReviews = $product->reviews_count;
                    $avgRating = $product->rating_average ?? 0;
                @endphp
                @if($totalReviews > 0)
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                        <div class="text-center lg:text-left">
                            <div class="text-5xl font-bold text-gray-900 dark:text-white">{{ number_format($avgRating, 1) }}</div>
                            <div class="flex items-center justify-center lg:justify-start gap-1 mt-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $totalReviews }} reviews</p>
                        </div>
                        <div class="lg:col-span-2 space-y-2">
                            @for($star = 5; $star >= 1; $star--)
                                @php $pct = $totalReviews > 0 ? (($ratingCounts[$star - 1] ?? 0) / $totalReviews) * 100 : 0; @endphp
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-8">{{ $star }} ★</span>
                                    <div class="flex-1 h-2.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-400 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 w-10 text-right">{{ $ratingCounts[$star - 1] ?? 0 }}</span>
                                </div>
                            @endfor
                        </div>
                    </div>
                @else
                    <div class="text-center py-10 bg-gray-50 dark:bg-gray-800/50 rounded-2xl mb-10">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        <p class="text-gray-500 dark:text-gray-400">No reviews yet. Be the first to review this product!</p>
                    </div>
                @endif

                {{-- Review Form --}}
                @auth
                    <div x-show="showForm" x-collapse class="mb-10 bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 lg:p-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Write Your Review</h3>
                        <form action="{{ route('products.reviews.store', $product->id) }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rating</label>
                                <div class="flex items-center gap-1" x-data="{ rating: 0 }">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" @click="rating = {{ $i }}" class="focus:outline-none">
                                            <svg class="w-8 h-8 transition-colors" :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        </button>
                                    @endfor
                                    <input type="hidden" name="rating" x-model="rating" :value="rating">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2" x-text="rating > 0 ? rating + ' star' + (rating > 1 ? 's' : '') : 'Select rating'"></span>
                                </div>
                                @error('rating') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review Title</label>
                                <input type="text" name="title" id="title" placeholder="Summarize your review" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors">
                            </div>
                            <div class="mb-4">
                                <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review</label>
                                <textarea name="body" id="body" rows="4" placeholder="Tell others about your experience..." class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-xl text-sm focus:border-market-500 dark:focus:border-market-400 outline-none transition-colors resize-none"></textarea>
                            </div>
                            <button type="submit" class="px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Submit Review</button>
                        </form>
                    </div>
                @endauth

                {{-- Review List --}}
                @if(isset($product->reviews) && $product->reviews->count() > 0)
                    <div class="space-y-6">
                        @foreach($product->reviews as $review)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-market-100 dark:bg-market-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-semibold text-market-700 dark:text-market-300">{{ substr($review->user?->name ?? 'A', 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->user?->name ?? 'Anonymous' }}</p>
                                            <p class="text-xs text-gray-400">{{ $review->created_at?->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= ($review->rating ?? 0) ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endfor
                                    </div>
                                </div>
                                @if($review->title)
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ $review->title }}</h4>
                                @endif
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $review->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
