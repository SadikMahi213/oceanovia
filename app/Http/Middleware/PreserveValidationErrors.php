<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreserveValidationErrors
{
    /**
     * Keep flashed validation errors alive across a background JSON request.
     *
     * Laravel ages flashed session data at the end of every session-using
     * request, so a background XHR that lands between a failed form post and
     * the redirect target consumes the errors and the user never sees the
     * validation summary.
     *
     * Re-applying the flash after the handler has run keeps the errors
     * available for the next page render, where they are consumed normally.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $errors = $request->session()->get('errors');

        $response = $next($request);

        if ($errors) {
            $request->session()->flash('errors', $errors);
        }

        return $response;
    }
}
