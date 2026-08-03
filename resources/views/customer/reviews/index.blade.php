<x-app-layout>
    @section('title', 'My Reviews')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-customer-sidebar :userName="Auth::user()->name" :userAvatar="Auth::user()->avatar_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Reviews</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Reviews you've written for products</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">Product</th>
                                        <th class="px-5 py-3">Rating</th>
                                        <th class="px-5 py-3">Review</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3">Date</th>
                                        <th class="px-5 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($reviews as $review)
                                        <tr x-data="{ showEditForm: false }" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0">
                                                        @if($review->product?->thumbnail)
                                                            <img src="{{ $review->product->thumbnail }}" alt="{{ $review->product->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <div class="w-full h-full flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <a href="{{ route('products.show', $review->product?->slug) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-market-600 dark:hover:text-market-400 truncate max-w-[180px] block">{{ $review->product?->name ?? 'Unknown Product' }}</a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-0.5">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    @endfor
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div x-show="!showEditForm" class="max-w-[200px]">
                                                    @if($review->title)
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->title }}</p>
                                                    @endif
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($review->review, 80) }}</p>
                                                </div>
                                                <div x-show="showEditForm" x-cloak>
                                                    <form method="POST" action="{{ route('customer.reviews.update', $review) }}" class="space-y-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <select name="rating" required
                                                            class="block w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-market-500 focus:ring-market-500">
                                                            <option value="5" {{ $review->rating == 5 ? 'selected' : '' }}>5 - Excellent</option>
                                                            <option value="4" {{ $review->rating == 4 ? 'selected' : '' }}>4 - Good</option>
                                                            <option value="3" {{ $review->rating == 3 ? 'selected' : '' }}>3 - Average</option>
                                                            <option value="2" {{ $review->rating == 2 ? 'selected' : '' }}>2 - Poor</option>
                                                            <option value="1" {{ $review->rating == 1 ? 'selected' : '' }}>1 - Terrible</option>
                                                        </select>
                                                        <input type="text" name="title" value="{{ old('title', $review->title) }}" placeholder="Review title"
                                                            class="block w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-market-500 focus:ring-market-500">
                                                        <textarea name="body" rows="2" required placeholder="Your review..."
                                                            class="block w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-market-500 focus:ring-market-500">{{ old('body', $review->review) }}</textarea>
                                                        <div class="flex items-center gap-2">
                                                            <button type="submit" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Save</button>
                                                            <button type="button" @click="showEditForm = false" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:underline">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                    ];
                                                @endphp
                                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$review->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ ucfirst($review->status) }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $review->created_at->format('M d, Y') }}</td>
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-2">
                                                    <button @click="showEditForm = !showEditForm" class="text-xs font-medium text-market-600 dark:text-market-400 hover:underline">Edit</button>
                                                    <form method="POST" action="{{ route('customer.reviews.destroy', $review) }}" class="inline" onsubmit="return confirm('Delete this review?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">No reviews yet</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Reviews you write will appear here</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($reviews->hasPages())
                            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                {{ $reviews->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
