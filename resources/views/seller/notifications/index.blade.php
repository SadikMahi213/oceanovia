<x-app-layout>
    @section('title', 'Notifications')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Stay updated with your store activity</p>
                        </div>
                        <form method="POST" action="{{ route('seller.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Mark All as Read
                            </button>
                        </form>
                    </div>

                    <div class="space-y-3">
                        @forelse($notifications as $notification)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden transition-colors {{ is_null($notification->read_at) ? 'border-l-4 border-l-market-500' : '' }}">
                                <div class="p-5">
                                    <div class="flex items-start gap-4">
                                        <div class="shrink-0">
                                            @php
                                                $icon = match($notification->type) {
                                                    'info' => ['bg-blue-50 dark:bg-blue-900/30', 'text-blue-600 dark:text-blue-400', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                                    'warning' => ['bg-yellow-50 dark:bg-yellow-900/30', 'text-yellow-600 dark:text-yellow-400', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z'],
                                                    'success' => ['bg-green-50 dark:bg-green-900/30', 'text-green-600 dark:text-green-400', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                                    default => ['bg-gray-50 dark:bg-gray-700', 'text-gray-600 dark:text-gray-400', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                                                };
                                            @endphp
                                            <div class="w-10 h-10 rounded-xl {{ $icon[0] }} flex items-center justify-center">
                                                <svg class="w-5 h-5 {{ $icon[1] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon[2] }}"/></svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <p class="text-sm {{ is_null($notification->read_at) ? 'font-semibold' : 'font-medium' }} text-gray-900 dark:text-white">
                                                        {{ $notification->title }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ Str::limit($notification->message, 120) }}</p>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                                    @if(is_null($notification->read_at))
                                                        <span class="w-2 h-2 bg-market-500 rounded-full"></span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(is_null($notification->read_at))
                                                <div class="mt-3">
                                                    <form method="POST" action="{{ route('seller.notifications.read', $notification) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Mark Read</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">No notifications yet</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">You're all caught up! New notifications will appear here</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if($notifications->hasPages())
                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
