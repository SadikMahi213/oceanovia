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
    The camera scanner for a product search field, shown inside a polished
    responsive modal.

    The button opens the modal with the camera view. A detected barcode is put
    into the search field the selector points at, and that field's form is
    submitted once. The camera is only requested when the modal opens and is
    released whenever the modal closes - including the escape key, a click on
    the backdrop, or a successful scan. Only the component that starts the
    camera can guarantee that.

    Focus is moved into the modal when it opens and restored to the trigger
    button when it closes.
--}}

<div x-data="barcodeScanner(@js($target), @js($readerId))"
     x-init="$watch('show', value => {
         if (value) { document.body.classList.add('overflow-y-hidden'); }
         else { document.body.classList.remove('overflow-y-hidden'); }
     })">
    <!-- Scan trigger button -->
    <button
        type="button"
        @click="open($event)"
        aria-label="Scan a barcode with the camera"
        title="Scan a barcode with the camera"
        class="absolute right-10 top-1/2 -translate-y-1/2 text-gray-500 hover:text-market-600 dark:text-gray-400 dark:hover:text-market-400 transition-colors"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2m0 10v2a2 2 0 01-2 2h-2M5 21H3a2 2 0 01-2-2v-2m14-4h.01M7 7h.01M11 7h2M7 11h2M7 15h2"/>
        </svg>
    </button>

    <!-- Modal backdrop / dialog -->
    <div
        x-show="show"
        x-cloak
        @keydown.escape.window="close()"
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="barcode-scanner-title"
        aria-describedby="barcode-scanner-description"
    >
        <!-- Subtle backdrop overlay -->
        <div
            x-show="show"
            class="fixed inset-0 transform transition-all"
            @click="close()"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        </div>

        <!-- Modal panel -->
        <div
            x-show="show"
            class="relative mb-6 bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <!-- Header -->
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="barcode-scanner-title" class="text-lg font-semibold text-gray-900 dark:text-white">Scan a Barcode</h3>
                        <p id="barcode-scanner-description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Align the UPC or EAN barcode within the frame. The code is put into the search field and the search runs.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="close()"
                        aria-label="Close the scanner"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 transition-colors p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Camera preview with scanning guide frame -->
                <div class="mt-5 bg-gray-900 rounded-xl overflow-hidden relative aspect-video sm:aspect-[4/3]">
                    <div id="{{ $readerId }}" class="w-full h-full"></div>

                    <!-- Scanning guide frame: helps users align the barcode -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-full max-w-xs h-36 sm:h-40 border-2 border-white/70 rounded-lg shadow-[0_0_0_2000px_rgba(0,0,0,0.55)] relative overflow-hidden">
                            <div class="absolute inset-0 border-2 border-white/80 rounded-lg -inset-0.5 pointer-events-none"></div>
                            <!-- Corner markers -->
                            <div class="absolute top-3 left-3 w-3 h-3 bg-white rounded-sm"></div>
                            <div class="absolute top-3 right-3 w-3 h-3 bg-white rounded-sm"></div>
                            <div class="absolute bottom-3 left-3 w-3 h-3 bg-white rounded-sm"></div>
                            <div class="absolute bottom-3 right-3 w-3 h-3 bg-white rounded-sm"></div>
                        </div>
                    </div>

                    <p class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 text-xs font-medium text-white/90 bg-black/60 px-3 py-1.5 rounded-full backdrop-blur-sm whitespace-nowrap">
                        Align the UPC or EAN barcode within the frame.
                    </p>
                </div>

                <!-- Scanning status -->
                <p x-show="status" x-cloak class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center" x-text="status"></p>

                <!-- Error state -->
                <div x-show="error" x-cloak class="mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-600 dark:text-red-400" x-text="error" role="alert"></div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3 flex justify-end border-t border-gray-100 dark:border-gray-700">
                <button
                    type="button"
                    @click="close()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
