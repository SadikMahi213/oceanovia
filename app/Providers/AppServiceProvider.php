<?php

namespace App\Providers;

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // API rate limiting
        RateLimiter::for('api', fn (Request $request) =>
            Limit::perMinute(100)->by($request->user()?->id ?: $request->ip())
        );

        // Auth rate limiting (login, register)
        RateLimiter::for('auth', fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        // Checkout rate limiting
        RateLimiter::for('checkout', fn (Request $request) =>
            Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        // Cart operations rate limiting
        RateLimiter::for('cart', fn (Request $request) =>
            Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        // Reviews rate limiting
        RateLimiter::for('reviews', fn (Request $request) =>
            Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        // Wishlist rate limiting
        RateLimiter::for('wishlist', fn (Request $request) =>
            Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        // Contact form rate limiting
        RateLimiter::for('contact', fn (Request $request) =>
            Limit::perMinute(3)->by($request->user()?->id ?: $request->ip())
        );
    }
}
