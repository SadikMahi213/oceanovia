{{--
    Renders the validation message list for a field.

    The element id is derived from the field name and the error bag, so it
    always matches the aria-describedby that <x-text-input> generates for
    the same field and bag. A form that uses a named error bag must pass the
    same bag to both components: bag="updatePassword" here and
    error-bag="updatePassword" on the matching <x-text-input>.
--}}
@props(['messages', 'field' => null, 'bag' => 'default'])

@if ($messages)
    @php
        $errorAttributes = ['class' => 'text-sm text-red-600 dark:text-red-400 space-y-1'];

        if ($field) {
            $errorAttributes['id'] = \App\Support\ValidationErrorId::for($field, $bag);
            $errorAttributes['data-error-for'] = $field;
            $errorAttributes['data-error-bag'] = $bag;
        }
    @endphp
    <ul {{ $attributes->merge($errorAttributes) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
