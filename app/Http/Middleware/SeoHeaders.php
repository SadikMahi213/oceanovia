<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeoHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $url = $request->url();

            $response->headers->set('X-Robots-Tag', 'index, follow');
            $response->headers->set('Link', "<{$url}>; rel=\"canonical\"");
        }

        return $response;
    }
}
