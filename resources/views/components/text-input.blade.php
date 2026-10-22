{{--
    Renders a text input.

    When the named field has a validation error the input is marked
    aria-invalid and points at the error element through aria-describedby.
    That element id is derived from the field name and the error bag, so a
    form using a named error bag must pass the same bag to the matching
    <x-input-error> component: error-bag="updatePassword" here and
    bag="updatePassword" there.
--}}
@props([
    'disabled' => false,
    'errorBag' => 'default',
])

@php
    $resolvedName = $attributes->get('name');
    $fieldHasError = false;

    if ($resolvedName) {
        $errorSource = view()->shared('errors') ?? new \Illuminate\Support\ViewErrorBag;

        $resolvedBag = $errorSource instanceof \Illuminate\Support\ViewErrorBag
            ? $errorSource->getBag($errorBag)
            : $errorSource;

        $fieldHasError = $resolvedBag->has($resolvedName);
    }

    $errorId = ($resolvedName && $fieldHasError)
        ? \App\Support\ValidationErrorId::for($resolvedName, $errorBag)
        : null;

    $describedBy = collect([
        $attributes->get('aria-describedby'),
        $errorId,
    ])->filter()->unique()->implode(' ');

    $inputAttributes = $attributes->getAttributes();

    if ($fieldHasError) {
        $inputAttributes['aria-invalid'] = 'true';
    }

    if ($describedBy !== '') {
        $inputAttributes['aria-describedby'] = $describedBy;
    }

    $attributes = new \Illuminate\View\ComponentAttributeBag($inputAttributes);
@endphp

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
