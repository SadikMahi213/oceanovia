<x-app-layout>
    @section('title', 'Audit Log #'.$auditLog->id)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-market-600 dark:text-market-400 hover:underline inline-flex items-center gap-1 mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back to Audit Logs
                            </a>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Log #{{ $auditLog->id }}</h1>
                        </div>
                        @php
                            $actionBadge = match($auditLog->action) {
                                'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $actionBadge }}">{{ ucfirst($auditLog->action) }}</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            @if($auditLog->old_values || $auditLog->new_values)
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Changes</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @if($auditLog->old_values)
                                            <div>
                                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Old Values</h4>
                                                <pre class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 overflow-x-auto max-h-64">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                        @if($auditLog->new_values)
                                            <div>
                                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">New Values</h4>
                                                <pre class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 overflow-x-auto max-h-64">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-4">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Details</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">User:</span> {{ $auditLog->user?->name ?? 'System' }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Action:</span> {{ ucfirst($auditLog->action) }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Resource:</span> {{ ucfirst($auditLog->resource_type) }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Resource ID:</span> {{ $auditLog->resource_id }}</p>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Request Info</h3>
                                <div class="space-y-2 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">IP Address:</span> {{ $auditLog->ip_address ?? '—' }}</p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">User Agent:</span><br><span class="text-xs break-all">{{ $auditLog->user_agent ?? '—' }}</span></p>
                                    <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Timestamp:</span> {{ $auditLog->created_at->format('M d, Y g:i:s A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
