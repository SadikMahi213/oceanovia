<header class="sticky top-0 z-50 bg-white/95 dark:bg-gray-950/95 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 transition-colors duration-200">
    {{-- Top Bar --}}
    <div class="hidden lg:block bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-9 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-6">
                <span>🇺🇸 Free shipping on orders over $50</span>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <span class="text-gray-700 dark:text-gray-300">Welcome, {{ Auth::user()->name }}</span>
                @else
                    <a href="{{ route('login') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Sign In</a>
                    <a href="{{ route('register') }}" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">Register</a>
                @endauth
                <button @click="$store.app.toggleDarkMode()" class="hover:text-market-600 dark:hover:text-market-400 transition-colors">
                    <svg x-show="!$store.app.darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="$store.app.darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Main Header --}}
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16 lg:h-20 gap-4">
            {{-- Logo --}}
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 lg:w-10 lg:h-10 bg-gradient-to-br from-market-500 to-market-700 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm lg:text-base">M</span>
                    </div>
                    <span class="hidden sm:block text-lg lg:text-xl font-bold text-gray-900 dark:text-white">
                        <span class="text-market-600">Mulit</span>Vendor
                    </span>
                </a>
            </div>

            {{-- Search Bar --}}
            <div class="flex-1 max-w-2xl hidden md:block" x-data>
                <form action="{{ route('products.search') }}" method="GET" class="relative">
                    <div class="flex items-center">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                name="q"
                                x-model="$store.search.query"
                                @input="$store.search.search($store.search.query)"
                                @focus="$store.search.show = true"
                                @click.away="$store.search.show = false"
                                @keydown.escape="$store.search.show = false"
                                placeholder="Search products, brands, categories..."
                                autocomplete="off"
                                class="w-full pl-4 pr-10 py-2.5 lg:py-3 bg-gray-100 dark:bg-gray-800 border-2 border-transparent focus:border-market-500 dark:focus:border-market-400 rounded-l-xl text-sm outline-none transition-all"
                            >
                            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-market-600 dark:hover:text-market-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </button>
                        </div>
                        <button type="submit" class="px-5 py-2.5 lg:py-3 bg-market-600 hover:bg-market-700 text-white rounded-r-xl text-sm font-medium transition-colors">
                            Search
                        </button>
                    </div>
                    {{-- Live search results --}}
                    <div x-show="$store.search.show && $store.search.query.length > 1" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50" x-cloak>
                        {{-- Loading --}}
                        <div x-show="$store.search.loading" class="flex items-center gap-2 px-4 py-3 text-sm text-gray-500">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Searching...
                        </div>
                        {{-- Results --}}
                        <template x-for="product in $store.search.results" :key="product.id">
                            <a :href="product.url" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                    <img :src="product.image || ''" :alt="product.name" loading="lazy" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="product.name"></p>
                                    <p class="text-xs text-market-600 dark:text-market-400 font-medium" x-text="'$' + parseFloat(product.price).toFixed(2)"></p>
                                </div>
                            </a>
                        </template>
                        {{-- No results --}}
                        <div x-show="!$store.search.loading && $store.search.results.length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                            No products found for "<span x-text="$store.search.query" class="font-medium"></span>"
                        </div>
                        {{-- View all link --}}
                        <a :href="'{{ route('products.search') }}?q=' + encodeURIComponent($store.search.query)" class="block px-4 py-2.5 text-sm font-medium text-center text-market-600 dark:text-market-400 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            View all results →
                        </a>
                    </div>
                </form>
            </div>

            {{-- Right Actions --}}
            <div class="flex items-center gap-1 lg:gap-2">
                {{-- Wishlist --}}
                <a href="{{ route('wishlist.index') }}" class="relative p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors" title="Wishlist">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" x-text="$store.wishlist.count" x-show="$store.wishlist.count > 0" x-cloak></span>
                </a>

                {{-- Cart --}}
                <a href="{{ route('cart.index') }}" class="relative p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors" title="Cart">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    <span class="absolute -top-0.5 -right-0.5 bg-market-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" x-text="$store.cart.count" x-show="$store.cart.count > 0" x-cloak></span>
                </a>

                {{-- User Menu --}}
                @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors ml-1">
                            <div class="w-8 h-8 bg-market-100 dark:bg-market-900 rounded-full flex items-center justify-center">
                                <span class="text-sm font-semibold text-market-700 dark:text-market-300">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <svg class="w-4 h-4 hidden lg:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50 animate-slide-down" x-cloak>
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium">{{ Auth::user()->full_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                My Dashboard
                            </a>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Profile Settings
                            </a>
                            <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                My Orders
                            </a>
                            <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 w-full transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors ml-2">
                        Sign In
                    </a>
                @endauth

                {{-- Dark mode toggle (mobile visible) --}}
                <button @click="$store.app.toggleDarkMode()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors" title="Toggle theme (Ctrl+Shift+L)">
                    <svg x-show="!$store.app.darkMode" class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="$store.app.darkMode" class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                {{-- Mobile menu toggle --}}
                <button @click="document.querySelector('.mobile-menu-panel').classList.toggle('hidden')" class="md:hidden p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors" x-data x-cloak>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Category Navigation / Mega Menu --}}
    <div class="hidden lg:block border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center gap-1">
                {{-- All Categories Mega Menu Trigger --}}
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-market-600 dark:hover:text-market-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        All Categories
                    </button>

                    {{-- Mega Menu Dropdown --}}
                    <div x-show="open" class="absolute left-0 top-full mt-0 w-[700px] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6 z-50 animate-slide-down" x-cloak>
                        <div class="grid grid-cols-3 gap-6">
                            <template x-for="category in $store.menu.categories" :key="category.slug">
                                <div>
                                    <a :href="'/categories/' + category.slug" class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white hover:text-market-600 dark:hover:text-market-400 mb-2 transition-colors">
                                        <span x-text="category.icon" class="text-lg"></span>
                                        <span x-text="category.name"></span>
                                    </a>
                                    <ul class="space-y-1">
                                        <template x-for="child in category.children" :key="child.slug">
                                            <li>
                                                <a :href="'/categories/' + child.slug" class="block text-sm text-gray-500 dark:text-gray-400 hover:text-market-600 dark:hover:text-market-400 py-0.5 transition-colors" x-text="child.name"></a>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <a href="/categories" class="text-sm font-medium text-market-600 dark:text-market-400 hover:text-market-700 dark:hover:text-market-300 transition-colors">View All Categories →</a>
                        </div>
                    </div>
                </div>

                {{-- Regular Nav Links --}}
                <a href="{{ route('home') }}" class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-market-600 dark:hover:text-market-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg transition-colors {{ request()->routeIs('home') ? 'text-market-600' : '' }}">Home</a>
                <a href="{{ route('products.index') }}" class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-market-600 dark:hover:text-market-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg transition-colors">Shop</a>
                <a href="{{ route('products.sellers') }}" class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-market-600 dark:hover:text-market-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg transition-colors">Featured Sellers</a>
                <a href="{{ route('products.deals') }}" class="px-4 py-3 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex items-center gap-1">
                    <span>🔥</span> Daily Deals
                </a>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div class="mobile-menu-panel hidden lg:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="px-4 py-4 space-y-1">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Home</a>
            <a href="{{ route('products.index') }}" class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Shop</a>
            <a href="{{ route('products.deals') }}" class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">🔥 Daily Deals</a>
            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
            <button @click="$store.app.toggleDarkMode()" class="flex items-center gap-3 w-full px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg x-show="!$store.app.darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="$store.app.darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span x-show="!$store.app.darkMode">Dark Mode</span>
                <span x-show="$store.app.darkMode">Light Mode</span>
            </button>
            <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>
            <template x-for="category in $store.menu.categories" :key="category.slug">
                <a :href="'/categories/' + category.slug" class="block px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <span x-text="category.icon" class="mr-2"></span>
                    <span x-text="category.name"></span>
                </a>
            </template>
        </div>
    </div>

    {{-- Mobile search bar --}}
    <div class="md:hidden px-4 pb-3">
        <form action="{{ route('products.search') }}" method="GET" class="relative">
            <input type="text" name="q" placeholder="Search products..." class="w-full pl-4 pr-10 py-2 bg-gray-100 dark:bg-gray-800 border-2 border-transparent focus:border-market-500 rounded-xl text-sm outline-none transition-all">
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>
</header>
