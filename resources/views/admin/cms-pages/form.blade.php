@php
$edit = isset($cmsPage);
$title = $edit ? 'Edit CMS Page' : 'Add CMS Page';
@endphp
<x-app-layout>
    @section('title', $title)
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update page details' : 'Create a new CMS page' }}</p>
                        </div>
                        <a href="{{ route('admin.cms-pages.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </a>
                    </div>

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">Please fix the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $edit ? route('admin.cms-pages.update', $cmsPage) : route('admin.cms-pages.store') }}" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Page Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="title" value="Title *" />
                                    <input type="text" id="title" name="title" value="{{ old('title', $cmsPage?->title) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="slug" value="Slug" />
                                    <input type="text" id="slug" name="slug" value="{{ old('slug', $cmsPage?->slug) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to auto-generate from title</p>
                                    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="content" value="Content" />
                                    <textarea id="content" name="content" rows="12"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('content', $cmsPage?->content) }}</textarea>
                                    <x-input-error :messages="$errors->get('content')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">SEO</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="meta_title" value="Meta Title" />
                                    <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $cmsPage?->meta_title) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('meta_title')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="meta_description" value="Meta Description" />
                                    <textarea id="meta_description" name="meta_description" rows="3"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('meta_description', $cmsPage?->meta_description) }}</textarea>
                                    <x-input-error :messages="$errors->get('meta_description')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="status" value="Status *" />
                                    <select id="status" name="status" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="draft" {{ old('status', $cmsPage?->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="published" {{ old('status', $cmsPage?->status) === 'published' ? 'selected' : '' }}>Published</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.cms-pages.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Page' : 'Create Page' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
