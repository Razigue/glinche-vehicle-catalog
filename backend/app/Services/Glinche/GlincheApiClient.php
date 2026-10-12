<?php

namespace App\Services\Glinche;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class GlincheApiClient
{
    public const TOKEN_CACHE_KEY = 'glinche_api_token';

    private const TOKEN_TTL_SECONDS = 50 * 60;

    private const TIMEOUT_SECONDS = 15;

    private const RETRY_TIMES = 3;

    private const RETRY_DELAY_MILLISECONDS = 500;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $email,
        private readonly string $password,
    ) {}

    /**
     * The V1 endpoint returns the whole stock in a single JSON array, without pagination.
     *
     * @return list<array<string, mixed>>
     *
     * @throws GlincheApiException
     */
    public function fetchVehicles(): array
    {
        try {
            $adverts = $this->authenticatedGet('partners/vehicles')->json();
        } catch (ConnectionException $exception) {
            throw new GlincheApiException('The Glinche API is unreachable.', previous: $exception);
        }

        if (! is_array($adverts) || ! array_is_list($adverts)) {
            throw new GlincheApiException('Unexpected vehicles payload: a JSON array was expected.');
        }

        return $adverts;
    }

    private function authenticatedGet(string $uri): Response
    {
        $response = $this->request()->withToken($this->token())->get($uri);

        if ($response->unauthorized()) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->request()->withToken($this->token())->get($uri);
        }

        return $this->ensureSuccessful($response);
    }

    private function token(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL_SECONDS, function (): string {
            $response = $this->request()->post('partners/login', [
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $token = $this->ensureSuccessful($response)->json('token');

            if (! is_string($token) || $token === '') {
                throw new GlincheApiException('The login response does not contain a token.');
            }

            return $token;
        });
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(
                self::RETRY_TIMES,
                self::RETRY_DELAY_MILLISECONDS,
                fn (Throwable $exception) => $this->isTransient($exception),
                throw: false,
            );
    }

    private function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $exception->response->serverError());
    }

    private function ensureSuccessful(Response $response): Response
    {
        if ($response->failed()) {
            throw new GlincheApiException(
                "The Glinche API responded with HTTP {$response->status()}.",
                previous: $response->toException(),
            );
        }

        return $response;
    }
}
