<?php

namespace App\Services\Glinche;

use App\Enums\Energy;
use App\Enums\Gearbox;

final readonly class VehicleData
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $externalId,
        public string $brandName,
        public string $brandSlug,
        public string $model,
        public ?string $version,
        public ?int $year,
        public int $mileage,
        public ?Energy $energy,
        public ?Gearbox $gearbox,
        public ?int $priceInCents,
        public ?string $photoUrl,
        public bool $isDeleted,
        public array $raw,
    ) {}
}
