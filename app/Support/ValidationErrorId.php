<?php

namespace App\Support;

use Illuminate\Support\Str;

class ValidationErrorId
{
    /**
     * Build the DOM id of the error message element for a field.
     *
     * The id is scoped by the validation error bag so that two forms on the
     * same page can validate the same field name (for example "password" in
     * the update-password form and in the delete-account modal) without
     * emitting duplicate ids. The default bag keeps the plain
     * "{field}-error" form so existing anchors stay stable.
     */
    public static function for(string $field, string $bag = 'default'): string
    {
        $field = Str::snake($field);

        if ($bag !== '' && $bag !== 'default') {
            $field = Str::snake($bag).'-'.$field;
        }

        return $field.'-error';
    }
}
