<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    {{-- Image skeleton --}}
    <div class="aspect-square bg-gray-100 dark:bg-gray-700 animate-skeleton"></div>
    {{-- Details skeleton --}}
    <div class="p-4 space-y-3">
        <div class="h-3 w-20 bg-gray-100 dark:bg-gray-700 rounded animate-skeleton"></div>
        <div class="h-4 w-full bg-gray-100 dark:bg-gray-700 rounded animate-skeleton"></div>
        <div class="h-4 w-3/4 bg-gray-100 dark:bg-gray-700 rounded animate-skeleton"></div>
        <div class="flex gap-1">
            @for($i = 0; $i < 5; $i++)
                <div class="w-3.5 h-3.5 bg-gray-100 dark:bg-gray-700 rounded animate-skeleton"></div>
            @endfor
        </div>
        <div class="flex items-center gap-2">
            <div class="h-6 w-16 bg-gray-100 dark:bg-gray-700 rounded animate-skeleton"></div>
            <div class="h-4 w-12 bg-gray-100 dark:bg-gray-700 rounded animate-skeleton"></div>
        </div>
        <div class="h-10 w-full bg-gray-100 dark:bg-gray-700 rounded-xl animate-skeleton"></div>
    </div>
</div>
