<x-app-layout>
    @section('title', 'Message from ' . $contactMessage->name)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Message Details</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">From {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @unless($contactMessage->is_read)
                                <form method="POST" action="{{ route('admin.contact-messages.read', $contactMessage) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Mark as Read
                                    </button>
                                </form>
                            @endunless
                            <a href="{{ route('admin.contact-messages.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back
                            </a>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</span>
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $contactMessage->name }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</span>
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        <a href="mailto:{{ $contactMessage->email }}" class="text-market-600 dark:text-market-400 hover:underline">{{ $contactMessage->email }}</a>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</span>
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $contactMessage->phone ?: '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</span>
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $contactMessage->created_at->format('F d, Y g:i A') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</span>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $contactMessage->subject }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</span>
                                <div class="mt-2 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $contactMessage->message }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
