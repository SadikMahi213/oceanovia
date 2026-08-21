@props(['product'])
<div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
    {{-- Image --}}
    <div class="relative aspect-square bg-gray-50 dark:bg-gray-700 overflow-hidden">
        <a href="{{ route('products.show', $product->slug) }}">
            @if($product->thumbnail)
                <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            @endif
        </a>

        {{-- Badges --}}
        <div class="absolute top-3 left-3 flex flex-col gap-1">
            @if($product->discount_percent)
                <span class="px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-lg">-{{ $product->discount_percent }}%</span>
            @endif
            @if($product->is_featured)
                <span class="px-2 py-0.5 bg-market-500 text-white text-xs font-bold rounded-lg">Featured</span>
            @endif
        </div>

        {{-- Wishlist Button --}}
        <button @click="$store.wishlist.toggle({{ $product->id }}); $store.toast.success($store.wishlist.has({{ $product->id }}) ? 'Added to wishlist' : 'Removed from wishlist')"
                class="absolute top-3 right-3 w-9 h-9 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white dark:hover:bg-gray-800 transition-all shadow-sm"
                :class="{ 'text-red-500': $store.wishlist.has({{ $product->id }}), 'text-gray-400': !$store.wishlist.has({{ $product->id }}) }">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        </button>
    </div>

    {{-- Details --}}
    <div class="p-4">
        {{-- Seller --}}
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">
            @if($product->seller)
                by <span class="text-market-600 dark:text-market-400">{{ $product->seller->name }}</span>
            @endif
        </p>

        {{-- Title --}}
        <a href="{{ route('products.show', $product->slug) }}">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2 hover:text-market-600 dark:hover:text-market-400 transition-colors mb-2">
                {{ $product->name }}
            </h3>
        </a>

        {{-- Rating --}}
        @if($product->reviews_count > 0)
            <div class="flex items-center gap-1.5 mb-2">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-3.5 h-3.5 {{ $i <= round($product->rating_average) ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500">({{ $product->reviews_count }})</span>
            </div>
        @else
            <div class="flex items-center gap-1.5 mb-2">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-3.5 h-3.5 text-gray-200 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500">No reviews</span>
            </div>
        @endif

        {{-- Price --}}
        <div class="flex items-center gap-2 mb-3">
            <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($product->price, 2) }}</span>
            @if($product->compare_price && $product->compare_price > $product->price)
                <span class="text-sm text-gray-400 line-through">${{ number_format($product->compare_price, 2) }}</span>
            @endif
        </div>

        {{-- Add to Cart --}}
        <button x-data="{ product: {{ Js::from(['id' => $product->id, 'name' => $product->name, 'price' => $product->price, 'sellerId' => $product->seller_id, 'supplierId' => $product->inventory?->supplier_id, 'weight' => $product->weight ?? 0]) }} }" @click="$store.cart.addItem(product); $store.toast.success('Added to cart')"
                class="w-full py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            Add to Cart
        </button>
    </div>
</div>
