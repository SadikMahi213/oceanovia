<footer class="bg-gray-900 dark:bg-gray-950 text-gray-300 border-t border-gray-800">
    {{-- Newsletter --}}
    <div class="border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-10 lg:py-14">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-xl font-bold text-white">Join our newsletter</h3>
                    <p class="text-sm text-gray-400 mt-1">Get exclusive deals, new arrivals, and seller stories.</p>
                </div>
                <form class="flex w-full max-w-md gap-2">
                    <input type="email" placeholder="Enter your email" class="flex-1 px-4 py-3 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white placeholder-gray-400 focus:border-market-500 outline-none transition-colors">
                    <button type="submit" class="px-6 py-3 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Subscribe</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Footer --}}
    <div class="max-w-7xl mx-auto px-4 py-10 lg:py-16">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 lg:gap-12">
            {{-- Brand --}}
            <div class="col-span-2 md:col-span-3 lg:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-gradient-to-br from-market-500 to-market-700 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">M</span>
                    </div>
                    <span class="text-lg font-bold text-white">MulitVendor</span>
                </a>
                <p class="text-sm text-gray-400 leading-relaxed mb-4">The premier USA marketplace connecting customers with authentic sellers and suppliers nationwide.</p>
                <div class="flex items-center gap-3">
                    <a href="#" aria-label="Facebook" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    <a href="#" aria-label="GitHub" class="w-9 h-9 bg-gray-800 hover:bg-gray-700 rounded-lg flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Shop</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('products.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">All Products</a></li>
                    <li><a href="{{ route('products.sellers') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Featured Sellers</a></li>
                    <li><a href="{{ route('products.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">New Arrivals</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'popular']) }}" class="text-sm text-gray-400 hover:text-white transition-colors">Best Sellers</a></li>
                    <li><a href="{{ route('products.deals') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Daily Deals</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Sell</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('register', ['role'=>'seller']) }}" class="text-sm text-gray-400 hover:text-white transition-colors">Start Selling</a></li>
                    <li><a href="{{ route('seller.dashboard') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Seller Dashboard</a></li>
                    <li><a href="{{ route('register', ['role'=>'supplier']) }}" class="text-sm text-gray-400 hover:text-white transition-colors">Become a Supplier</a></li>
                    <li><a href="{{ route('faq') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Seller Resources</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Support</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('pages.show', 'help-center') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Help Center</a></li>
                    <li><a href="{{ route('customer.orders.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Track Order</a></li>
                    <li><a href="{{ route('customer.orders.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Returns</a></li>
                    <li><a href="{{ route('contact') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="{{ route('faq') }}" class="text-sm text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                </ul>
            </div>

            <div class="col-span-2 md:col-span-1">
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Company</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('pages.show', 'about-us') }}" class="text-sm text-gray-400 hover:text-white transition-colors">About Us</a></li>
                    <li><a href="{{ route('pages.show', 'privacy-policy') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms-of-service') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Bottom Bar --}}
    <div class="border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-400">&copy; {{ date('Y') }} MulitVendor USA. All rights reserved.</p>
            <div class="flex items-center gap-4 text-sm text-gray-400">
                <span>🇺🇸 USA Marketplace</span>
                <span class="hidden sm:inline">·</span>
                <span>Made with ❤️ for American Sellers</span>
            </div>
        </div>
    </div>
</footer>
