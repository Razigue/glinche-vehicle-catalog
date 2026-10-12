<?php

namespace App\Services\Glinche;

use App\Enums\Energy;
use App\Enums\Gearbox;
use Illuminate\Support\Facades\Validator;
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
            'reference' => ['required', 'string'],
            'deleteDate' => ['nullable', 'string'],
            'pictures' => ['array'],
            'pictures.*.type' => ['required', 'string'],
            'pictures.*.url' => ['required', 'url'],
            'vehicle' => ['required', 'array'],
            'vehicle.manufacturer' => ['required', 'string'],
            'vehicle.model' => ['required', 'string'],
            'vehicle.finish' => ['nullable', 'string'],
            'vehicle.year' => ['nullable', 'integer'],
            'vehicle.mileage' => ['required', 'integer', 'min:0'],
            'vehicle.energy' => ['nullable', 'string'],
            'vehicle.gearbox' => ['nullable', 'string'],
            'vehicle.prices' => ['nullable', 'array'],
        ]);

        $vehicle = $advert['vehicle'];

        return new VehicleData(
            externalId: $advert['reference'],
            brandName: trim($vehicle['manufacturer']),
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
        if (! is_string($amount) || ! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            return null;
        }

        return (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    /**
     * @param  list<array{type: string, url: string}>  $pictures
     */
    private function photoUrl(array $pictures): ?string
    {
        $mainPicture = collect($pictures)->firstWhere('type', self::MAIN_PICTURE_TYPE) ?? $pictures[0] ?? null;

        return $mainPicture['url'] ?? null;
    }

    private function nullIfBlank(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
