<x-app-layout>
    @section('title', 'Support Tickets')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Support Tickets</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage customer support requests</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                            <form method="GET" class="flex flex-wrap items-center gap-3">
                                <select name="status" class="text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <option value="">All Statuses</option>
                                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                                <select name="priority" class="text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <option value="">All Priorities</option>
                                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                                <div class="relative flex-1 min-w-[200px]">
                                    <input type="text" name="search" placeholder="Search by subject..." value="{{ request('search') }}" class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 pl-9">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Filter</button>
                                @if(request()->anyFilled(['status', 'priority', 'search']))
                                    <a href="{{ route('admin.support-tickets.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors">Clear</a>
                                @endif
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Subject</th>
                                        <th class="px-5 py-3">User</th>
                                        <th class="px-5 py-3">Priority</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Assigned To</th>
                                        <th class="px-5 py-3">Created</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($tickets as $ticket)
                                        @php
                                            $priorityBadge = match($ticket->priority) {
                                                'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                                'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                            $statusBadge = match($ticket->status) {
                                                'open' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'resolved' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[200px] block">{{ \Illuminate\Support\Str::limit($ticket->subject, 60) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $ticket->user?->name ?? '—' }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityBadge }}">
                                                    {{ ucfirst($ticket->priority) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">
                                                    {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $ticket->assignee?->name ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $ticket->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-market-600 dark:text-market-400 bg-market-50 dark:bg-market-900/20 hover:bg-market-100 dark:hover:bg-market-900/30 rounded-lg transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        View
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No tickets found</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">No support tickets match your criteria</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($tickets->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $tickets->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
