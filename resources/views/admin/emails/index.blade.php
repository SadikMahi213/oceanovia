<x-app-layout>
    @section('title', 'Email Sender')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Sender</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Compose and send emails from {{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;</p>
                    </div>

                    @if(session('success'))
                        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-4">
                            <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <h2 class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">Please fix the following errors:</h2>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.emails.send') }}" class="space-y-6" x-data='{ recipients: @js(old("recipients", "")) }'>
                        @csrf

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Message</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="recipients" value="Recipients (comma separated) *" />
                                    <textarea id="recipients" name="recipients" rows="2" required
                                        x-model="recipients"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"
                                        placeholder="customer@example.com, seller@example.com&#10;Another valid address separated by a comma or new line"></textarea>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple recipients with a comma, semicolon or new line.</div>
                                    <x-input-error :messages="$errors->get('recipients')" class="mt-1" />
                                    <div class="mt-1 text-xs font-medium text-gray-600 dark:text-gray-300" x-text="recipients.split(/[\s,;]+/).filter(e => e).length + ' recipient(s)'"></div>
                                </div>
                                <div>
                                    <x-input-label for="subject" value="Subject *" />
                                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"
                                        placeholder="Your message subject">
                                    <x-input-error :messages="$errors->get('subject')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="body_type" value="Format *" />
                                    <select id="body_type" name="body_type" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="text" {{ old('body_type') === 'html' ? '' : 'selected' }}>Plain text</option>
                                        <option value="html" {{ old('body_type') === 'html' ? 'selected' : '' }}>HTML</option>
                                    </select>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">HTML is only safe because this feature is restricted to administrators.</div>
                                    <x-input-error :messages="$errors->get('body_type')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="message" value="Message *" />
                                    <textarea id="message" name="message" rows="8" required maxlength="50000"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm font-mono"
                                        placeholder="Write your message here{{ old('body_type') === 'html' ? ' (HTML allowed, administrator only)' : '' }}">{{ old('message') }}</textarea>
                                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Send Email
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delivery History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Recipient</th>
                                        <th class="px-5 py-3">Subject</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Requested</th>
                                        <th class="px-5 py-3">Sent</th>
                                        <th class="px-5 py-3">Error</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($logs as $log)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->recipient_email }}</span>
                                                @if($log->user)
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">by {{ $log->user->name }}</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $log->subject }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $statusClasses = [
                                                        'requested' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                        'sent' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                    ];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$log->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($log->status) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $log->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $log->sent_at ? $log->sent_at->format('M d, Y H:i') : '—' }}
                                            </td>
                                            <td class="px-5 py-4 text-sm text-red-600 dark:text-red-400 max-w-xs truncate" title="{{ $log->error_message }}">
                                                {{ $log->error_message ?: '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-12 text-center">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">No emails sent yet</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sent messages will appear here with their delivery status.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($logs->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $logs->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>