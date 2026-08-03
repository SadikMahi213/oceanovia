<x-app-layout>
    @section('title', $message->subject)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('supplier.messages.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </a>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $message->subject }}</h1>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $message->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                @if($message->sender_type === 'admin')
                                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $message->sender_type === 'admin' ? 'Administrator' : $message->sender?->name ?? 'Customer' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($message->sender_type) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{!! nl2br(e($message->message)) !!}</p>
                        </div>
                    </div>

                    @if($message->replies->count() > 0)
                        <div class="mt-6 space-y-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Replies</h2>
                            @foreach($message->replies as $reply)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $reply->user?->name ?? 'Unknown' }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{!! nl2br(e($reply->message)) !!}</p>
                                        @if($reply->attachments)
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach($reply->attachments as $attachment)
                                                    <a href="{{ Storage::url($attachment) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        {{ basename($attachment) }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Write a Reply</h2>
                        </div>
                        <form method="POST" action="{{ route('supplier.messages.reply', $message) }}" enctype="multipart/form-data" class="p-5 space-y-4">
                            @csrf
                            <div>
                                <textarea name="message" rows="4" required placeholder="Type your reply..."
                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"></textarea>
                                <x-input-error :messages="$errors->get('message')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="attachments" value="Attachments" />
                                <input type="file" id="attachments" name="attachments[]" multiple
                                    class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                <x-input-error :messages="$errors->get('attachments')" class="mt-1" />
                                <x-input-error :messages="$errors->get('attachments.*')" class="mt-1" />
                            </div>
                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>