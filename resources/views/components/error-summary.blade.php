@props([
    'bag' => 'default',
    'id' => 'error-summary',
    'heading' => 'Please correct the following before continuing:',
    'errors' => null,
])

@php
    $errorSource = $errors ?: (view()->shared('errors') ?? new \Illuminate\Support\ViewErrorBag);

    $errorBag = $errorSource instanceof \Illuminate\Support\ViewErrorBag
        ? $errorSource->getBag($bag)
        : $errorSource;

    $summaryMessages = $errorBag->toArray();
@endphp

@if (! empty($summaryMessages))
    <div {{ $attributes->merge([
        'class' => 'rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 mb-6',
    ]) }}
        id="{{ $id }}"
        role="alert"
        tabindex="-1"
        aria-labelledby="{{ $id }}-heading"
        data-error-summary
        data-error-bag="{{ $bag }}">
        <h2 id="{{ $id }}-heading" class="text-base font-semibold mb-2">{{ $heading }}</h2>
        <ul class="list-disc list-inside space-y-1">
            @foreach ($summaryMessages as $field => $message)
                @foreach ((array) $message as $singleMessage)
                    <li>
                        @if (is_string($field) && $field !== '')
                            <a href="#{{ \Illuminate\Support\Str::snake($field) }}"
                               data-error-field="{{ \Illuminate\Support\Str::snake($field) }}"
                               class="underline hover:text-red-800 dark:hover:text-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 rounded">{{ $singleMessage }}</a>
                        @else
                            <span>{{ $singleMessage }}</span>
                        @endif
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
