<x-app-layout>
    @section('title', 'Reviews')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reviews</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all customer product reviews</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Status</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            <input type="text" name="search" placeholder="Search product..." value="{{ request('search') }}"
                                class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-market-500 focus:ring-market-500 w-48">
                            <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-market-500 text-white rounded-lg hover:bg-market-600 transition-colors">Search</button>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">Customer</th>
                                        <th class="px-5 py-3">Rating</th>
                                        <th class="px-5 py-3">Review</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($reviews as $review)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($review->product?->thumbnail)
                                                            <img src="{{ $review->product->thumbnail }}" alt="" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center text-xs text-gray-400">—</div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[180px]">{{ $review->product?->name ?? '—' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-900 dark:text-white">{{ $review->user?->name ?? '—' }}</td>
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-0.5">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $review->rating)
                                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        @else
                                                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        @endif
                                                    @endfor
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-[220px] truncate">{{ Str::limit($review->body ?? '—', 80) }}</td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $badge = match($review->is_approved ? 'approved' : ($review->is_approved === null ? 'pending' : 'rejected')) {
                                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                                    {{ $review->is_approved ? 'Approved' : ($review->is_approved === null ? 'Pending' : 'Rejected') }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $review->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="px-2.5 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors" {{ $review->is_approved ? 'disabled' : '' }}>Approve</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline" onsubmit="return confirm('Reject this review?')">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors" {{ !$review->is_approved ? 'disabled' : '' }}>Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No reviews found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($reviews->hasPages())
                            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                                {{ $reviews->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
