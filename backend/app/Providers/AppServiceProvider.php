<?php

namespace App\Providers;

use App\Services\Glinche\GlincheApiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GlincheApiClient::class, fn () => new GlincheApiClient(
            baseUrl: (string) config('services.glinche.url'),
            email: (string) config('services.glinche.email'),
            password: (string) config('services.glinche.password'),
        ));
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
