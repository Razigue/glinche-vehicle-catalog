<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Builds an advert shaped like the Glinche V1 partners feed.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function glincheAdvert(array $overrides = []): array
{
    return array_replace_recursive([
        'reference' => 'GLI00004457',
        'title' => 'Toyota Yaris Cross Hybride 130h Design 2WD',
        'editDate' => '2026-04-17T08:35:10+02:00',
        'insertDate' => '2025-06-11T16:16:20+02:00',
        'availabilityDate' => '2024-10-30T00:00:00+01:00',
        'deleteDate' => null,
        'pictures' => [
            ['type' => 'IMAGE', 'url' => 'https://glinche.test/pictures/side.jpg'],
            ['type' => 'MAIN', 'url' => 'https://glinche.test/pictures/main.jpg'],
        ],
        'vehicle' => [
            'manufacturer' => 'Toyota',
            'model' => 'YARIS CROSS',
            'finish' => 'Hybride 130h Design 2WD',
            'energy' => 'EH',
            'year' => 2024,
            'mileage' => 20,
            'gearbox' => 'AUT',
            'prices' => [
                'merchantPrice' => '26700.00',
                'reclaimableVAT' => false,
                'taxCode' => 'VAT',
                'catalogPrice' => '31350.00',
            ],
        ],
    ], $overrides);
}
