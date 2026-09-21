<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('monitoring-login', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Register the Brevo HTTP API mail transport
        Mail::extend('brevo', function (array $config = []) {
            $apiKey = $config['api_key'] ?? config('services.brevo.api_key', '');
            return new BrevoTransport($apiKey);
        });
    }
}