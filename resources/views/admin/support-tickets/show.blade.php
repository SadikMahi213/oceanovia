<x-app-layout>
    @section('title', 'Ticket #'.$ticket->id)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <a href="{{ route('admin.support-tickets.index') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline inline-flex items-center gap-1 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back to Tickets
                            </a>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ticket->subject }}</h1>
                        </div>
                        <div class="flex items-center gap-3">
                            @php
                                $statusBadge = match($ticket->status) {
                                    'open' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'resolved' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                    'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                };
                                $priorityBadge = match($ticket->priority) {
                                    'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                    'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $statusBadge }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $priorityBadge }}">{{ ucfirst($ticket->priority) }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Message</h3>
                                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    {{ $ticket->message }}
                                </div>
                            </div>

                            @if($ticket->status !== 'closed')
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Update Status</h3>
                                    <form method="POST" action="{{ route('admin.support-tickets.update', $ticket) }}" class="space-y-4">
                                        @csrf
                                        @method('PATCH')
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                                <select name="status" class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                                                <select name="priority" class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                                    <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                                                <select name="assigned_to" class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    <option value="">Unassigned</option>
                                                    @foreach($admins as $admin)
                                                        <option value="{{ $admin->id }}" {{ $ticket->assigned_to === $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Update Ticket
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Customer Details</h3>
                <div class="space-y-2 text-sm">
                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Name:</span> {{ $ticket->user?->name ?? '—' }}</p>
                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $ticket->user?->email ?? '—' }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Ticket Info</h3>
                <div class="space-y-2 text-sm">
                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Assigned To:</span> {{ $ticket->assignee?->name ?? 'Unassigned' }}</p>
                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Created:</span> {{ $ticket->created_at->format('M d, Y g:i A') }}</p>
                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Updated:</span> {{ $ticket->updated_at->format('M d, Y g:i A') }}</p>
                    @if($ticket->resolved_at)
                        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Resolved:</span> {{ $ticket->resolved_at->format('M d, Y g:i A') }}</p>
                    @endif
                </div>
            </div>

            @if($ticket->status !== 'closed')
                <form method="POST" action="{{ route('admin.support-tickets.close', $ticket) }}" onsubmit="return confirm('Close this ticket?')">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Close Ticket
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
</div>
</div>
</section>
</x-app-layout>
