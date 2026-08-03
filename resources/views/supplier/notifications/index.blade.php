<x-app-layout>
    @section('title', 'Notifications')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Stay updated with your activity</p>
                        </div>
                        <form method="POST" action="{{ route('supplier.notifications.read-all') }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/></svg>
                                Mark All as Read
                            </button>
                        </form>
                    </div>

                    <div class="space-y-3">
                        @forelse($notifications as $notification)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden {{ !$notification->read ? 'border-l-4 border-l-market-500' : '' }}">
                                <div class="p-5">
                                    <div class="flex items-start gap-4">
                                        @php
                                            $iconColors = [
                                                'order' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                                                'review' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
                                                'payout' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                                                'message' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
                                                'alert' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                                                'inventory' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
                                            ];
                                            $icon = $iconColors[$notification->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
                                        @endphp
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $icon }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @switch($notification->type)
                                                    @case('order')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                        @break
                                                    @case('review')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                                        @break
                                                    @case('payout')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        @break
                                                    @case('message')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                                        @break
                                                    @case('alert')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        @break
                                                    @case('inventory')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                                        @break
                                                    @default
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                @endswitch
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    @if(!$notification->read)
                                                        <span class="w-2 h-2 rounded-full bg-market-500 shrink-0 mt-1.5"></span>
                                                    @endif
                                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white {{ !$notification->read ? '' : '' }}">{{ $notification->title }}</h3>
                                                </div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $notification->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $notification->message }}</p>
                                            <div class="flex items-center gap-3 mt-2">
                                                @if($notification->link)
                                                    <a href="{{ $notification->link }}" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">View Details</a>
                                                @endif
                                                @if(!$notification->read)
                                                    <form method="POST" action="{{ route('supplier.notifications.read', $notification) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline">Mark Read</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">All caught up!</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">No notifications at this time</p>
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