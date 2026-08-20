@php
    $announcementItems = \App\Models\Announcement::active()->latest()->get();

    $announcementStyles = [
        'info' => [
            'bar'  => 'bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100',
            'chip' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
        'warning' => [
            'bar'  => 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-100',
            'chip' => 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0L10.29 3.86z"/>',
        ],
        'success' => [
            'bar'  => 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800 text-green-900 dark:text-green-100',
            'chip' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
        'alert' => [
            'bar'  => 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800 text-red-900 dark:text-red-100',
            'chip' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
    ];
@endphp

@if($announcementItems->isNotEmpty())
    <div class="w-full border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-950" x-data x-cloak>
        <div class="max-w-7xl mx-auto px-4 py-3 lg:py-4 space-y-3">
            @foreach($announcementItems as $announcement)
                @php
                    $style = $announcementStyles[$announcement->type] ?? $announcementStyles['info'];

                    $content = e($announcement->content);
                    $content = preg_replace(
                        '/\+(\d{9,15})/',
                        '<a href="https://wa.me/$1" target="_blank" rel="noopener noreferrer" class="font-semibold underline underline-offset-2 hover:opacity-80">+$1</a>',
                        $content
                    );
                    $content = preg_replace(
                        '~(https?://[^\s<"]+)~',
                        '<a href="$1" target="_blank" rel="noopener noreferrer" class="font-semibold underline underline-offset-2 hover:opacity-80">$1</a>',
                        $content
                    );

                    preg_match('/(\+\d{9,15})/', $announcement->content, $phoneMatch);
                    $whatsappNumber = $phoneMatch[0] ?? null;
                    $whatsappLink = $whatsappNumber ? 'https://wa.me/' . ltrim($whatsappNumber, '+') : null;
                @endphp

                <div
                    x-data="{ dismissed: localStorage.getItem('mv-announcement-{{ $announcement->id }}') === '1' }"
                    x-show="!dismissed"
                    x-cloak
                    class="flex items-start gap-3 sm:gap-4 rounded-2xl border px-4 py-3 sm:px-5 sm:py-4 {{ $style['bar'] }}"
                >
                    <div class="hidden sm:flex w-10 h-10 shrink-0 rounded-full {{ $style['chip'] }} items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $style['icon'] !!}</svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        @if($announcement->title)
                            <p class="text-sm font-bold uppercase tracking-wide flex items-center gap-1.5">
                                <svg class="w-4 h-4 sm:hidden shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $style['icon'] !!}</svg>
                                {{ $announcement->title }}
                            </p>
                        @endif
                        <p class="text-sm sm:text-base leading-relaxed break-words">{!! nl2br($content) !!}</p>

                        @if($whatsappLink)
                            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 mt-3 px-4 py-2 rounded-xl bg-[#25D366] hover:bg-[#1ebe5b] text-white text-sm font-semibold transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Message on WhatsApp
                            </a>
                        @endif
                    </div>

                    <button type="button" @click="dismissed = true; localStorage.setItem('mv-announcement-{{ $announcement->id }}', '1')"
                            class="shrink-0 p-1.5 rounded-lg opacity-60 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-colors" aria-label="Dismiss announcement">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endif