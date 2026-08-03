<x-app-layout>
    @section('title', 'My Coupons')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Coupons</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your available, used, and expired coupons</p>
                        </div>
                    </div>

                    <div x-data="{ tab: 'available' }" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="border-b border-gray-100 dark:border-gray-700">
                            <div class="flex">
                                <button @click="tab = 'available'" :class="{ 'border-market-500 text-market-600 dark:text-market-400': tab === 'available', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': tab !== 'available' }" class="px-6 py-3.5 text-sm font-medium border-b-2 transition-colors">Available ({{ $available->count() }})</button>
                                <button @click="tab = 'used'" :class="{ 'border-market-500 text-market-600 dark:text-market-400': tab === 'used', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': tab !== 'used' }" class="px-6 py-3.5 text-sm font-medium border-b-2 transition-colors">Used ({{ $used->count() }})</button>
                                <button @click="tab = 'expired'" :class="{ 'border-market-500 text-market-600 dark:text-market-400': tab === 'expired', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': tab !== 'expired' }" class="px-6 py-3.5 text-sm font-medium border-b-2 transition-colors">Expired ({{ $expired->count() }})</button>
                            </div>
                        </div>

                        <div class="p-5">
                            <div x-show="tab === 'available'">
                                @if($available->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($available as $coupon)
                                            <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800 hover:shadow-sm transition-shadow">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <span class="text-lg font-bold text-gray-900 dark:text-white tracking-wide">{{ $coupon->code }}</span>
                                                        <span class="ml-2 inline-flex px-2 py-0.5 bg-market-100 text-market-800 dark:bg-market-900/30 dark:text-market-400 text-xs font-bold rounded-lg">{{ $coupon->discount_value }}{{ $coupon->discount_type === 'percentage' ? '% OFF' : ' OFF' }}</span>
                                                    </div>
                                                    <button onclick="navigator.clipboard.writeText('{{ $coupon->code }}'); window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: 'Copied!'}}))"
                                                            class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline shrink-0">Copy Code</button>
                                                </div>
                                                @if($coupon->description)
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $coupon->description }}</p>
                                                @endif
                                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400 dark:text-gray-500">
                                                    @if($coupon->min_order_amount)
                                                        <span>Min. order: ${{ number_format($coupon->min_order_amount, 2) }}</span>
                                                    @endif
                                                    <span>Expires: {{ $coupon->expires_at->format('M d, Y') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-12">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No available coupons</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Check back later for new offers</p>
                                    </div>
                                @endif
                            </div>

                            <div x-show="tab === 'used'" x-cloak>
                                @if($used->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($used as $coupon)
                                            <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-4 bg-gray-50 dark:bg-gray-800/50 opacity-60">
                                                <div class="flex items-start justify-between mb-2">
                                                    <span class="text-lg font-bold text-gray-400 dark:text-gray-500 tracking-wide line-through">{{ $coupon->code }}</span>
                                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-bold rounded-lg">{{ $coupon->discount_value }}{{ $coupon->discount_type === 'percentage' ? '% OFF' : ' OFF' }}</span>
                                                </div>
                                                @if($coupon->description)
                                                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-2">{{ $coupon->description }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-12">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No used coupons</p>
                                    </div>
                                @endif
                            </div>

                            <div x-show="tab === 'expired'" x-cloak>
                                @if($expired->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($expired as $coupon)
                                            <div class="border border-red-200 dark:border-red-900/30 rounded-xl p-4 bg-red-50 dark:bg-red-900/10">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div>
                                                        <span class="text-lg font-bold text-red-400 dark:text-red-400 tracking-wide">{{ $coupon->code }}</span>
                                                        <span class="ml-2 inline-flex px-2 py-0.5 bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 text-xs font-bold rounded-lg">Expired</span>
                                                    </div>
                                                </div>
                                                @if($coupon->description)
                                                    <p class="text-sm text-red-400/70 mb-2">{{ $coupon->description }}</p>
                                                @endif
                                                @if($coupon->expires_at)
                                                    <span class="text-xs text-red-400/70">Expired {{ $coupon->expires_at->diffForHumans() }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-12">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No expired coupons</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
        window.addEventListener('toast', (e) => {
            window.dispatchEvent(new CustomEvent('toast', {detail: e.detail}));
        });
    </script>
    @endpush
</x-app-layout>
