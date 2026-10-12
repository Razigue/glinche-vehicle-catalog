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
        ->brandSlug->toBe('toyota')
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

it('accepts prices sent as numbers', function (int|float $amount, int $expectedCents) {
    $vehicle = (new VehicleMapper)->map(glincheAdvert([
        'vehicle' => ['prices' => ['merchantPrice' => $amount]],
    ]));

    expect($vehicle->priceInCents)->toBe($expectedCents);
})->with([
    'integer' => [26700, 2_670_000],
    'float' => [26700.5, 2_670_050],
]);

it('leaves the price empty when it exceeds the storable range', function () {
    $vehicle = (new VehicleMapper)->map(glincheAdvert([
        'vehicle' => ['prices' => ['merchantPrice' => '100000000.00']],
    ]));

    expect($vehicle->priceInCents)->toBeNull();
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

it('ignores pictures without a usable URL', function () {
    $advert = glincheAdvert();
    $advert['pictures'] = [
        ['type' => 'MAIN', 'url' => 'not a url'],
        ['type' => 'IMAGE'],
        ['type' => 'IMAGE', 'url' => 'javascript://glinche.test/%0Aalert(1)'],
        ['type' => 'IMAGE', 'url' => 'https://glinche.test/pictures/side.jpg'],
    ];

    expect((new VehicleMapper)->map($advert)->photoUrl)->toBe('https://glinche.test/pictures/side.jpg');
});

it('leaves the photo empty when the advert has no picture', function (?array $pictures) {
    $advert = glincheAdvert();
    $advert['pictures'] = $pictures;

    expect((new VehicleMapper)->map($advert)->photoUrl)->toBeNull();
})->with([
    'empty list' => [[]],
    'null' => [null],
]);

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

it('never keeps a picture that is not served over HTTP', function (string $url) {
    $advert = glincheAdvert();
    $advert['pictures'] = [['type' => 'MAIN', 'url' => $url]];

    expect((new VehicleMapper)->map($advert)->photoUrl)->toBeNull();
})->with([
    'javascript:alert(1)',
    'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
    'file:///etc/passwd',
]);

it('rejects values that do not fit in the database columns', function (string $field, mixed $value) {
    $advert = glincheAdvert();
    data_set($advert, $field, $value);

    (new VehicleMapper)->map($advert);
})->throws(ValidationException::class)->with([
    'reference too long' => ['reference', str_repeat('A', 256)],
    'manufacturer too long' => ['vehicle.manufacturer', str_repeat('A', 256)],
    'model too long' => ['vehicle.model', str_repeat('A', 256)],
    'version too long' => ['vehicle.finish', str_repeat('A', 256)],
    'negative year' => ['vehicle.year', -1],
    'mileage overflow' => ['vehicle.mileage', 4_294_967_296],
]);

it('rejects a manufacturer that cannot be turned into a slug', function () {
    (new VehicleMapper)->map(glincheAdvert(['vehicle' => ['manufacturer' => '比亚迪']]));
})->throws(ValidationException::class, 'slug');
