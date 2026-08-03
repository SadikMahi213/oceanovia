<x-app-layout>
    @section('title', $message->subject)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $message->subject }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Message from {{ $message->sender?->name ?? 'Unknown' }}</p>
                        </div>
                        <a href="{{ route('seller.messages.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Messages
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-market-100 dark:bg-market-900/30 flex items-center justify-center shrink-0">
                                    <span class="text-sm font-medium text-market-700 dark:text-market-300">{{ substr($message->sender?->name ?? '?', 0, 2) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $message->sender?->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $message->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                {!! nl2br(e($message->body ?? '')) !!}
                            </div>
                        </div>
                    </div>

                    @if($message->replies && $message->replies->count() > 0)
                        <div class="space-y-4 mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Replies</h2>
                            @foreach($message->replies as $reply)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ substr($reply->sender?->name ?? '?', 0, 2) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $reply->sender?->name ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $reply->created_at->format('M d, Y g:i A') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                                            {!! nl2br(e($reply->body ?? '')) !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Reply</h2>
                        </div>
                        <div class="p-5">
                            <form method="POST" action="{{ route('seller.messages.reply', $message) }}" class="space-y-4">
                                @csrf
                                @method('POST')
                                <div>
                                    <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your Message</label>
                                    <textarea id="body" name="body" rows="5" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-market-500 focus:border-market-500" placeholder="Write your reply..." required></textarea>
                                </div>
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Send Reply
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
