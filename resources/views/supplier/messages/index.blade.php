<x-app-layout>
    @section('title', 'Messages')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Messages</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Communications from admin and customers</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse($messages as $message)
                            <a href="{{ route('supplier.messages.show', $message) }}" class="block bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all p-5 {{ $message->unread ? 'border-l-4 border-l-market-500' : '' }}">
                                <div class="flex items-start gap-4">
                                    <div class="shrink-0 mt-0.5">
                                        @if($message->sender_type === 'admin')
                                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @if($message->unread)
                                                    <span class="w-2 h-2 rounded-full bg-market-500 shrink-0"></span>
                                                @endif
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate {{ $message->unread ? '' : '' }}">{{ $message->subject }}</h3>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $message->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">{{ Str::limit($message->message, 80) }}</p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No messages</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Messages from admin and customers will appear here</p>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if($messages->hasPages())
                        <div class="mt-6">
                            {{ $messages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>