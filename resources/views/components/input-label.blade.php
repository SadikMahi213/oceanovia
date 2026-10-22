@props(['value' => null, 'for' => null])

@php
    $labelAttributes = $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']);

    if ($for) {
        $labelAttributes = $labelAttributes->merge(['for' => $for]);
    }
@endphp

<label {{ $labelAttributes }}>
    {{ $value ?? $slot }}
</label>
