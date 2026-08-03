@php
$edit = isset($taxRate);
$title = $edit ? 'Edit Tax Rate' : 'Add Tax Rate';
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $edit ? 'Update tax rate details' : 'Create a new tax rate' }}</p>
                        </div>
                        <a href="{{ route('admin.tax-rates.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
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

                    <form method="POST" action="{{ $edit ? route('admin.tax-rates.update', $taxRate) : route('admin.tax-rates.store') }}" class="space-y-6">
                        @csrf
                        @if($edit) @method('PUT') @endif

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tax Rate Details</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="state_code" value="State *" />
                                    <select id="state_code" name="state_code" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <option value="">Select a state</option>
                                        @foreach($states as $code => $name)
                                            <option value="{{ $code }}" {{ old('state_code', $taxRate?->state_code) === $code ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('state_code')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="rate" value="Rate (decimal, e.g. 0.08 for 8%) *" />
                                    <input type="number" step="0.0001" min="0" max="1" id="rate" name="rate" value="{{ old('rate', $taxRate?->rate) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('rate')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="name" value="Name *" />
                                    <input type="text" id="name" name="name" value="{{ old('name', $taxRate?->name) }}" required
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $taxRate?->is_active ?? true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-market-600 shadow-sm focus:ring-market-500">
                                    <x-input-label for="is_active" value="Active" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.tax-rates.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $edit ? 'Update Tax Rate' : 'Create Tax Rate' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
