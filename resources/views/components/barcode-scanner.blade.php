@props([
    'target' => '#product-search-input',
    'readerId' => null,
])

@php
    $readerId = $readerId ?: 'barcode-reader-'.substr(md5($target), 0, 8);
@endphp

{{--
    High-end glassmorphism fullscreen barcode scanner modal.

    Heavy backdrop-blur background, centered camera feed with rounded borders,
    glowing purple/green laser scan line, minimalist controls. The scanner
    engine (html5-qrcode, UPC/EAN formats, focus management, camera lifecycle)
    is unchanged.
--}}

<div x-data="barcodeScanner(@js($target), @js($readerId))"
     x-init="$watch('show', value => {
         if (value) { document.body.classList.add('overflow-hidden'); }
         else { document.body.classList.remove('overflow-hidden'); }
     })">

    <!-- Trigger button -->
    <button
        type="button"
        @click="open($event)"
        aria-label="Scan a barcode with the camera"
        class="absolute right-10 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/60 dark:bg-gray-800/60 backdrop-blur-md border border-white/40 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-white/80 dark:hover:bg-gray-800/80 transition-all flex items-center justify-center shadow-sm"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2m0 10v2a2 2 0 01-2 2h-2M5 21H3a2 2 0 01-2-2v-2m14-4h.01M7 7h.01M11 7h2M7 11h2M7 15h2"/>
        </svg>
    </button>

    <!-- Fullscreen glass modal -->
    <div
        x-show="show"
        x-cloak
        @keydown.escape.window="close()"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-white/5 dark:bg-black/10 backdrop-blur-[20px] transition-opacity duration-300"
        role="dialog"
        aria-modal="true"
        aria-labelledby="glass-scan-title"
        aria-describedby="glass-scan-desc"
    >
        <!-- Backdrop click to close -->
        <button
            @click="close()"
            class="absolute inset-0 w-full h-full z-0"
            aria-hidden="true"
        ></button>

        <!-- Camera preview container: rounded borders, centered -->
        <div class="relative z-10 w-full max-w-md sm:max-w-lg aspect-[3/4] mx-4 sm:mx-0 bg-white/10 dark:bg-white/5 backdrop-blur-md border border-white/30 dark:border-white/20 rounded-[2rem] overflow-hidden shadow-2xl">
            <div id="{{ $readerId }}" class="w-full h-full bg-black"></div>

            <!-- Laser scan line + corner brackets -->
            <div class="absolute inset-0 z-20 pointer-events-none overflow-hidden">
                <div
                    class="absolute left-0 right-0 top-[-10%] h-[3px]
                         bg-gradient-to-r from-transparent via-purple-500 via-green-400 to-transparent
                         shadow-[0_0_16px_rgba(139,92,246,0.9),0_0_32px_rgba(16,185,129,0.7)]
                         animate-scanline z-20"
                ></div>
                <div class="absolute top-4 left-4 w-12 h-12 border-t-2 border-l-2 border-white/60 rounded-tl-[1.2rem]"></div>
                <div class="absolute top-4 right-4 w-12 h-12 border-t-2 border-r-2 border-white/60 rounded-tr-[1.2rem]"></div>
                <div class="absolute bottom-4 left-4 w-12 h-12 border-b-2 border-l-2 border-white/60 rounded-bl-[1.2rem]"></div>
                <div class="absolute bottom-4 right-4 w-12 h-12 border-b-2 border-r-2 border-white/60 rounded-br-[1.2rem]"></div>
            </div>

            <!-- Minimalist close button -->
            <button
                @click="close()"
                aria-label="Close scanner"
                class="absolute top-4 right-4 z-30 w-10 h-10 rounded-full bg-black/30 backdrop-blur-md border border-white/40 text-white hover:bg-white/20 transition-all flex items-center justify-center"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Header overlay -->
        <div class="absolute top-8 left-1/2 -translate-x-1/2 z-30 flex flex-col items-center gap-2 pointer-events-none">
            <h3 id="glass-scan-title" class="text-white text-xl font-semibold tracking-tight drop-shadow">Scan a Barcode</h3>
            <p id="glass-scan-desc" class="text-white/80 text-sm drop-shadow">Align the UPC or EAN barcode within the frame</p>
        </div>

        <!-- Status / error overlay -->
        <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-30 flex flex-col items-center gap-1 pointer-events-none w-full max-w-sm px-4">
            <p x-show="status" x-cloak class="text-white text-sm font-medium drop-shadow px-4 py-2 rounded-full bg-white/10 backdrop-blur border border-white/20" x-text="status"></p>
            <div x-show="error" x-cloak class="bg-red-500/20 backdrop-blur-sm border border-red-500/40 text-red-300 px-4 py-2 rounded-full drop-shadow text-sm text-center" role="alert" x-text="error"></div>
        </div>

        <!-- Manual entry hint -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-30 text-white/60 text-xs tracking-wide drop-shadow pointer-events-none">
            Type the barcode manually to search
        </div>
    </div>
</div>

<style>
    @keyframes scanline {
        0% { transform: translateY(-10%); }
        100% { transform: translateY(110%); }
    }
    .animate-scanline {
        animation: scanline 3s ease-in-out infinite;
    }
</style>
