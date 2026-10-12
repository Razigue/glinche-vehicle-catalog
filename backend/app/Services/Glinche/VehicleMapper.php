<?php

namespace App\Services\Glinche;

use App\Enums\Energy;
use App\Enums\Gearbox;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VehicleMapper
{
    private const MAIN_PICTURE_TYPE = 'MAIN';

    /**
     * @param  array<string, mixed>  $advert
     *
     * @throws ValidationException
     */
    public function map(array $advert): VehicleData
    {
        Validator::validate($advert, [
            'reference' => ['required', 'string', 'max:255'],
            'deleteDate' => ['nullable', 'string'],
            'pictures' => ['nullable', 'array'],
            'vehicle' => ['required', 'array'],
            'vehicle.manufacturer' => ['required', 'string', 'max:255'],
            'vehicle.model' => ['required', 'string', 'max:255'],
            'vehicle.finish' => ['nullable', 'string', 'max:255'],
            'vehicle.year' => ['nullable', 'integer', 'between:0,65535'],
            'vehicle.mileage' => ['required', 'integer', 'between:0,4294967295'],
            'vehicle.energy' => ['nullable', 'string'],
            'vehicle.gearbox' => ['nullable', 'string'],
            'vehicle.prices' => ['nullable', 'array'],
        ]);

        $vehicle = $advert['vehicle'];
        $brandName = trim($vehicle['manufacturer']);
        $brandSlug = Str::slug($brandName);

        if ($brandSlug === '') {
            throw ValidationException::withMessages([
                'vehicle.manufacturer' => 'The manufacturer cannot be converted to a URL slug.',
            ]);
        }

        return new VehicleData(
            externalId: $advert['reference'],
            brandName: $brandName,
            brandSlug: $brandSlug,
            model: trim($vehicle['model']),
            version: $this->nullIfBlank($vehicle['finish'] ?? null),
            year: $vehicle['year'] ?? null,
            mileage: $vehicle['mileage'],
            energy: Energy::tryFrom($vehicle['energy'] ?? ''),
            gearbox: Gearbox::tryFrom($vehicle['gearbox'] ?? ''),
            priceInCents: $this->priceInCents($vehicle['prices'] ?? []),
            photoUrl: $this->photoUrl($advert['pictures'] ?? []),
            isDeleted: ($advert['deleteDate'] ?? null) !== null,
            raw: $advert,
        );
    }

    /**
     * The public price is optional in the feed, so the merchant price is used as a fallback.
     *
     * @param  array<string, mixed>  $prices
     */
    private function priceInCents(array $prices): ?int
    {
        foreach (['price', 'merchantPrice'] as $key) {
            $cents = $this->toCents($prices[$key] ?? null);

            if ($cents !== null && $cents > 0) {
                return $cents;
            }
        }

        return null;
    }

    private function toCents(mixed $amount): ?int
    {
        if (is_int($amount) || is_float($amount)) {
            $amount = number_format($amount, 2, '.', '');
        }

        if (! is_string($amount) || ! preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            return null;
        }

        return (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    /**
     * The main picture is preferred, otherwise the first picture with a usable URL.
     *
     * @param  array<mixed>  $pictures
     */
    private function photoUrl(array $pictures): ?string
    {
        $validPictures = collect($pictures)->filter(fn (mixed $picture) => is_array($picture)
            && $this->isUsablePictureUrl($picture['url'] ?? null));

        $picture = $validPictures->firstWhere('type', self::MAIN_PICTURE_TYPE) ?? $validPictures->first();

        return $picture['url'] ?? null;
    }

    private function isUsablePictureUrl(mixed $url): bool
    {
        return is_string($url)
            && strlen($url) <= 2048
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function nullIfBlank(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
