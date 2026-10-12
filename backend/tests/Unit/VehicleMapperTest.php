<?php

use App\Enums\Energy;
use App\Enums\Gearbox;
use App\Services\Glinche\VehicleMapper;
use Illuminate\Validation\ValidationException;

it('maps an advert to vehicle data', function () {
    $advert = glincheAdvert();

    $vehicle = (new VehicleMapper)->map($advert);

    expect($vehicle)
        ->externalId->toBe('GLI00004457')
        ->brandName->toBe('Toyota')
        ->model->toBe('YARIS CROSS')
        ->version->toBe('Hybride 130h Design 2WD')
        ->year->toBe(2024)
        ->mileage->toBe(20)
        ->energy->toBe(Energy::PetrolHybrid)
        ->gearbox->toBe(Gearbox::Automatic)
        ->priceInCents->toBe(2_670_000)
        ->photoUrl->toBe('https://glinche.test/pictures/main.jpg')
        ->isDeleted->toBeFalse()
        ->raw->toBe($advert);
});

it('prefers the public price over the merchant price', function () {
    $vehicle = (new VehicleMapper)->map(glincheAdvert([
        'vehicle' => ['prices' => ['price' => '27990.5']],
    ]));

    expect($vehicle->priceInCents)->toBe(2_799_050);
});

it('leaves the price empty when no positive price is provided', function () {
    $vehicle = (new VehicleMapper)->map(glincheAdvert([
        'vehicle' => ['prices' => ['merchantPrice' => '0.00']],
    ]));

    expect($vehicle->priceInCents)->toBeNull();
});

it('falls back to the first picture when there is no main picture', function () {
    $advert = glincheAdvert();
    $advert['pictures'] = [['type' => 'IMAGE', 'url' => 'https://glinche.test/pictures/side.jpg']];

    expect((new VehicleMapper)->map($advert)->photoUrl)->toBe('https://glinche.test/pictures/side.jpg');
});

it('leaves the photo empty when the advert has no picture', function () {
    $advert = glincheAdvert();
    $advert['pictures'] = [];

    expect((new VehicleMapper)->map($advert)->photoUrl)->toBeNull();
});

it('ignores energy and gearbox codes missing from the documentation', function () {
    $vehicle = (new VehicleMapper)->map(glincheAdvert([
        'vehicle' => ['energy' => 'XX', 'gearbox' => 'XXX'],
    ]));

    expect($vehicle)->energy->toBeNull()->gearbox->toBeNull();
});

it('flags adverts that have a deletion date', function () {
    $vehicle = (new VehicleMapper)->map(glincheAdvert(['deleteDate' => '2026-05-01T10:00:00+02:00']));

    expect($vehicle->isDeleted)->toBeTrue();
});

it('rejects an advert without the fields required to display it', function (string $field) {
    $advert = glincheAdvert();
    data_forget($advert, $field);

    (new VehicleMapper)->map($advert);
})->throws(ValidationException::class)->with([
    'reference',
    'vehicle.manufacturer',
    'vehicle.model',
    'vehicle.mileage',
]);
