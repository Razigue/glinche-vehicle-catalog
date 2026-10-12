<?php

namespace App\Providers;

use App\Services\Glinche\GlincheApiClient;
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
}
