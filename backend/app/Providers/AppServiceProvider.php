<?php

namespace App\Providers;

use App\Services\Glinche\GlincheApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GlincheApiClient::class, fn () => new GlincheApiClient(
            baseUrl: config()->string('services.glinche.url'),
            email: config()->string('services.glinche.email'),
            password: config()->string('services.glinche.password'),
        ));
    }
}
