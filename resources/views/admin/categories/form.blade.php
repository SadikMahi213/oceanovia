@php
$edit = isset($category);
$title = $edit ? 'Edit Category' : 'Add Category';
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update category details' : 'Create a new category' }}</p>
                        </div>
                        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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

                    <form method="POST" action="{{ $edit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Category Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="name" value="Name *" />
                                    <input type="text" id="name" name="name" value="{{ old('name', $category?->name) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="slug" value="Slug" />
                                    <input type="text" id="slug" name="slug" value="{{ old('slug', $category?->slug) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm"
                                        placeholder="Auto-generated from name if left blank">
                                    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="parent_id" value="Parent Category" />
                                    <select id="parent_id" name="parent_id"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="">No Parent (Root)</option>
                                        @foreach($parentCategories as $pc)
                                            <option value="{{ $pc->id }}" {{ (string) old('parent_id', $category?->parent_id) === (string) $pc->id ? 'selected' : '' }}>{{ $pc->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="description" value="Description" />
                                    <textarea id="description" name="description" rows="3"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('description', $category?->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="image" value="Image" />
                                    <input type="file" id="image" name="image" accept="image/*"
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/20 file:text-market-700 dark:file:text-market-400 hover:file:bg-market-100 dark:hover:file:bg-market-900/30 cursor-pointer">
                                    @if($edit && $category->image_url)
                                        <div class="mt-2 flex items-center gap-3">
                                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Current image</span>
                                        </div>
                                    @endif
                                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="sort_order" value="Sort Order" />
                                        <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
                                    </div>
                                    <div class="space-y-3 pt-6">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="is_featured" value="0">
                                            <input type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $category?->is_featured ?? false) ? 'checked' : '' }}
                                                class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 checked:bg-market-600 checked:border-market-600 dark:checked:bg-market-600 dark:checked:border-market-600 shadow-sm focus:ring-market-500 focus:ring-2 focus:ring-offset-0">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Featured</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="hidden" name="status" value="0">
                                            <input type="checkbox" id="status" name="status" value="1" {{ old('status', $category?->status ?? true) ? 'checked' : '' }}
                                                class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 checked:bg-market-600 checked:border-market-600 dark:checked:bg-market-600 dark:checked:border-market-600 shadow-sm focus:ring-market-500 focus:ring-2 focus:ring-offset-0">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Category' : 'Create Category' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
