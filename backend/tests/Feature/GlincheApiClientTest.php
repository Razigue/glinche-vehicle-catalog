<?php

use App\Services\Glinche\GlincheApiClient;
use App\Services\Glinche\GlincheApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();
});

it('logs in and fetches the vehicles with the bearer token', function () {
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'secret-token']),
        'glinche.test/api/partners/vehicles' => Http::response([glincheAdvert()]),
    ]);

    $adverts = app(GlincheApiClient::class)->fetchVehicles();

    expect($adverts)->toHaveCount(1)->and($adverts[0]['reference'])->toBe('GLI00004457');
    Http::assertSent(fn (Request $request) => $request->url() === 'https://glinche.test/api/partners/login'
        && $request['email'] === 'partner@glinche.test'
        && $request['password'] === 'secret');
    Http::assertSent(fn (Request $request) => $request->url() === 'https://glinche.test/api/partners/vehicles'
        && $request->hasHeader('Authorization', 'Bearer secret-token'));
});

it('reuses the cached token between calls', function () {
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'secret-token']),
        'glinche.test/api/partners/vehicles' => Http::response([]),
    ]);

    app(GlincheApiClient::class)->fetchVehicles();
    app(GlincheApiClient::class)->fetchVehicles();

    Http::assertSentCount(3);
});

it('logs in again when the cached token is rejected', function () {
    Cache::put(GlincheApiClient::TOKEN_CACHE_KEY, 'expired-token');
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'fresh-token']),
        'glinche.test/api/partners/vehicles' => function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer fresh-token')
                ? Http::response([glincheAdvert()])
                : Http::response(['message' => 'Unauthenticated.'], 401);
        },
    ]);

    expect(app(GlincheApiClient::class)->fetchVehicles())->toHaveCount(1)
        ->and(Cache::get(GlincheApiClient::TOKEN_CACHE_KEY))->toBe('fresh-token');
});

it('retries when the API has a transient server error', function () {
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'secret-token']),
        'glinche.test/api/partners/vehicles' => Http::sequence()
            ->push('Bad Gateway', 502)
            ->push([glincheAdvert()]),
    ]);

    expect(app(GlincheApiClient::class)->fetchVehicles())->toHaveCount(1);
});

it('fails when the credentials are refused', function () {
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['message' => 'Invalid credentials.'], 401),
    ]);

    app(GlincheApiClient::class)->fetchVehicles();
})->throws(GlincheApiException::class, 'HTTP 401');

it('fails when the API keeps returning server errors', function () {
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'secret-token']),
        'glinche.test/api/partners/vehicles' => Http::response('Service Unavailable', 503),
    ]);

    app(GlincheApiClient::class)->fetchVehicles();
})->throws(GlincheApiException::class, 'HTTP 503');

it('fails when the API is unreachable', function () {
    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    app(GlincheApiClient::class)->fetchVehicles();
})->throws(GlincheApiException::class, 'unreachable');

it('fails when the vehicles payload is not a list of adverts', function (mixed $payload) {
    Http::fake([
        'glinche.test/api/partners/login' => Http::response(['token' => 'secret-token']),
        'glinche.test/api/partners/vehicles' => Http::response($payload),
    ]);

    app(GlincheApiClient::class)->fetchVehicles();
})->throws(GlincheApiException::class, 'JSON array of objects')->with([
    'object' => [['data' => []]],
    'list of scalars' => [['GLI00004457']],
]);
