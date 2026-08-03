<x-app-layout>
    @section('title', 'Messages')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Messages</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Conversations with customers</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        @forelse($messages as $message)
                            <a href="{{ route('seller.messages.show', $message) }}" class="block border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-start gap-4 px-5 py-4">
                                    <div class="w-10 h-10 rounded-full bg-market-100 dark:bg-market-900/30 flex items-center justify-center shrink-0">
                                        <span class="text-sm font-medium text-market-700 dark:text-market-300">{{ substr($message->sender?->name ?? '?', 0, 2) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center gap-2">
                                                @if(!($message->is_read ?? true))
                                                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                                                @endif
                                                <h3 class="text-sm font-medium {{ !($message->is_read ?? true) ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }} truncate">{{ $message->subject }}</h3>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $message->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit(strip_tags($message->body ?? ''), 80) }}</p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">No messages yet</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Customer messages will appear here</p>
                                </div>
                            </div>
                        @endforelse

                        @if(method_exists($messages, 'hasPages') && $messages->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $messages->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
