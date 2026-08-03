<x-app-layout>
    @section('title', 'My Wishlist')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Wishlist</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="`${$store.wishlist.count} saved item${$store.wishlist.count !== 1 ? 's' : ''}`"></p>
                        </div>
                    </div>

                    <div x-data="wishlistPage()" x-init="load()" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        <template x-for="item in items" :key="item.id">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden group">
                                <a :href="`/products/${item.slug}`" class="block aspect-square bg-gray-50 dark:bg-gray-700 relative overflow-hidden">
                                    <img :src="item.image || item.thumbnail" :alt="item.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </a>
                                <div class="p-3">
                                    <a :href="`/products/${item.slug}`" class="block text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></a>
                                    <p class="text-sm font-bold text-market-600 dark:text-market-400 mt-1" x-text="`$${parseFloat(item.price || item.sale_price || 0).toFixed(2)}`"></p>
                                    <button @click="remove(item.id)" class="mt-2 w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="!loading && items.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center mt-6">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-16 h-16 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            <div>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">Your wishlist is empty</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Save your favorite items to your wishlist!</p>
                            </div>
                            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors mt-2">
                                Browse Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
        function wishlistPage() {
            return {
                items: [],
                loading: true,
                async load() {
                    try {
                        const resp = await fetch('/wishlist/items');
                        this.items = await resp.json();
                    } catch(e) { this.items = []; }
                    this.loading = false;
                },
                async remove(id) {
                    try {
                        const resp = await fetch(`/wishlist/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
                        if (resp.ok) {
                            this.items = this.items.filter(i => i.id !== id);
                            if (window.$store?.wishlist) { window.$store.wishlist.count = this.items.length; }
                        }
                    } catch(e) {}
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
