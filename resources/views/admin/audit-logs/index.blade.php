<x-app-layout>
    @section('title', 'Audit Logs')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Logs</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track all administrative actions</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                            <form method="GET" class="flex flex-wrap items-center gap-3">
                                <select name="action" class="text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <option value="">All Actions</option>
                                    @foreach($actions as $a)
                                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                                    @endforeach
                                </select>
                                <select name="resource_type" class="text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <option value="">All Resources</option>
                                    @foreach($resourceTypes as $rt)
                                        <option value="{{ $rt }}" {{ request('resource_type') === $rt ? 'selected' : '' }}>{{ ucfirst($rt) }}</option>
                                    @endforeach
                                </select>
                                <div class="relative flex-1 min-w-[200px]">
                                    <input type="text" name="search" placeholder="Search action or resource..." value="{{ request('search') }}" class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 pl-9">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">Filter</button>
                                @if(request()->anyFilled(['action', 'resource_type', 'search']))
                                    <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors">Clear</a>
                                @endif
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">User</th>
                                        <th class="px-5 py-3">Action</th>
                                        <th class="px-5 py-3">Resource</th>
                                        <th class="px-5 py-3">Resource ID</th>
                                        <th class="px-5 py-3">IP Address</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($logs as $log)
                                        @php
                                            $actionBadge = match($log->action) {
                                                'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $log->user?->name ?? 'System' }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $actionBadge }}">
                                                    {{ ucfirst($log->action) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($log->resource_type) }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $log->resource_id }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->format('M d, Y g:i A') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-market-600 dark:text-market-400 bg-market-50 dark:bg-market-900/20 hover:bg-market-100 dark:hover:bg-market-900/30 rounded-lg transition-colors">
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
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No audit logs</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">No logs match your criteria</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($logs->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $logs->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
