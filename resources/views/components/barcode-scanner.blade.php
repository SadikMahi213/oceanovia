@props([
    'target' => '#product-search-input',
    'readerId' => null,
])

@php
    // The camera renders into an element with an id, so the id has to be
    // unique on the page. It is derived from the search field the scanner
    // belongs to, which keeps it stable and unique.
    $readerId = $readerId ?: 'barcode-reader-'.substr(md5($target), 0, 8);
@endphp

{{--
    The camera scanner for a product search field.

    The button opens a dialog with the camera view. A detected barcode is put
    into the search field the selector points at, and that field's form is
    submitted once. The camera is only requested when the button is pressed.

    The dialog is part of this component instead of the shared modal
    component, because the camera has to be released whenever the dialog
    closes - including the escape key and a click on the backdrop - and only
    the component that starts the camera can guarantee that.
--}}

<div x-data="barcodeScanner(@js($target), @js($readerId))">
    <button
        type="button"
        @click="open()"
        aria-label="Scan a barcode with the camera"
        title="Scan a barcode with the camera"
        class="absolute right-10 top-1/2 -translate-y-1/2 text-gray-500 hover:text-market-600 dark:text-gray-400 dark:hover:text-market-400 transition-colors"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2m0 10v2a2 2 0 01-2 2h-2M5 21H3a2 2 0 01-2-2v-2m14-4h.01M7 7h.01M11 7h2M7 11h2M7 15h2"/>
        </svg>
    </button>

    <div
        x-show="show"
        x-cloak
        @keydown.escape.window="close()"
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        role="dialog"
        aria-modal="true"
        aria-label="Scan a barcode"
    >
        <div class="fixed inset-0 transform transition-all" @click="close()">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto">
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Scan a barcode</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Point the camera at a UPC or EAN barcode. The code is put into the search field and the search runs.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="close()"
                        aria-label="Close the scanner"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-300 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div id="{{ $readerId }}" class="mt-4 w-full overflow-hidden rounded-xl bg-gray-900"></div>

                <p x-show="status" x-cloak class="mt-3 text-sm text-gray-500 dark:text-gray-400" x-text="status"></p>
                <p x-show="error" x-cloak class="mt-3 text-sm text-red-600 dark:text-red-400" x-text="error"></p>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3 flex justify-end">
                <button
                    type="button"
                    @click="close()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
