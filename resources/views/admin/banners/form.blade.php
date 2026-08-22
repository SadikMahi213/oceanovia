@php
$edit = isset($banner);
$title = $edit ? 'Edit Banner' : 'Add Banner';
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update banner details' : 'Create a new banner' }}</p>
                        </div>
                        <a href="{{ route('admin.banners.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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

                    <form method="POST" action="{{ $edit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Banner Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="title" value="Title *" />
                                    <input type="text" id="title" name="title" value="{{ old('title', $banner?->title) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="subtitle" value="Subtitle" />
                                    <input type="text" id="subtitle" name="subtitle" value="{{ old('subtitle', $banner?->subtitle) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('subtitle')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="link" value="Link URL" />
                                    <input type="text" id="link" name="link" value="{{ old('link', $banner?->link) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('link')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="btn_text" value="Button Text" />
                                    <input type="text" id="btn_text" name="btn_text" value="{{ old('btn_text', $banner?->btn_text) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('btn_text')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Images</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="image" value="Banner Image *" />
                                    <input type="file" id="image" name="image" accept="image/*" {{ $edit ? '' : 'required' }}
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/20 file:text-market-700 dark:file:text-market-400 hover:file:bg-market-100 dark:hover:file:bg-market-900/30 transition-colors">
                                    @if($edit && $banner?->imageUrl)
                                        <div class="mt-2">
                                            <img src="{{ $banner->imageUrl }}" alt="Current banner image" class="w-40 h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Current image</p>
                                        </div>
                                    @endif
                                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="mobile_image" value="Mobile Image" />
                                    <input type="file" id="mobile_image" name="mobile_image" accept="image/*"
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/20 file:text-market-700 dark:file:text-market-400 hover:file:bg-market-100 dark:hover:file:bg-market-900/30 transition-colors">
                                    @if($edit && $banner?->mobileImageUrl)
                                        <div class="mt-2">
                                            <img src="{{ $banner->mobileImageUrl }}" alt="Current mobile image" class="w-40 h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Current mobile image</p>
                                        </div>
                                    @endif
                                    <x-input-error :messages="$errors->get('mobile_image')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Appearance & Placement</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="section" value="Section *" />
                                    <select id="section" name="section" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="hero" {{ old('section', $banner?->section) === 'hero' ? 'selected' : '' }}>Hero</option>
                                        <option value="promo" {{ old('section', $banner?->section) === 'promo' ? 'selected' : '' }}>Promo</option>
                                        <option value="featured" {{ old('section', $banner?->section) === 'featured' ? 'selected' : '' }}>Featured</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('section')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="sort_order" value="Sort Order" />
                                    <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="text_color" value="Text Color" />
                                    <input type="color" id="text_color" name="text_color" value="{{ old('text_color', $banner?->text_color ?? '#ffffff') }}"
                                        class="mt-1 block w-full h-10 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm focus:border-market-500 focus:ring-market-500 cursor-pointer">
                                    <x-input-error :messages="$errors->get('text_color')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="bg_color" value="Background Color" />
                                    <input type="color" id="bg_color" name="bg_color" value="{{ old('bg_color', $banner?->bg_color ?? '#000000') }}"
                                        class="mt-1 block w-full h-10 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm focus:border-market-500 focus:ring-market-500 cursor-pointer">
                                    <x-input-error :messages="$errors->get('bg_color')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-center gap-3">
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" id="status" name="status" value="1" {{ old('status', $banner?->status ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 shadow-sm focus:ring-market-500">
                                    <x-input-label for="status" value="Active" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.banners.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Banner' : 'Create Banner' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
