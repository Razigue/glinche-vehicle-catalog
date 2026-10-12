<?php

use App\Enums\Energy;
use App\Enums\Gearbox;
use App\Http\Controllers\VehicleController;
use App\Models\Brand;
use App\Models\Vehicle;

it('returns the vehicle details', function () {
    $vehicle = Vehicle::factory()
        ->for(Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']))
        ->create([
            'external_id' => 'GLI00004457',
            'model' => 'YARIS CROSS',
            'version' => 'Hybride 130h Design 2WD',
            'year' => 2024,
            'mileage' => 20,
            'energy' => Energy::PetrolHybrid,
            'gearbox' => Gearbox::Automatic,
            'price' => 2_670_000,
            'photo_url' => 'https://glinche.test/pictures/main.jpg',
        ]);

    $this->getJson('/api/vehicles')
        ->assertOk()
        ->assertJsonPath('data', [[
            'id' => $vehicle->id,
            'reference' => 'GLI00004457',
            'brand' => ['name' => 'Toyota', 'slug' => 'toyota'],
            'model' => 'YARIS CROSS',
            'version' => 'Hybride 130h Design 2WD',
            'year' => 2024,
            'mileage' => 20,
            'energy' => 'Hybride essence',
            'gearbox' => 'Automatique',
            'price_in_cents' => 2_670_000,
            'photo_url' => 'https://glinche.test/pictures/main.jpg',
        ]]);
});

it('filters the vehicles by brand slug', function () {
    $peugeot = Brand::factory()->create(['slug' => 'peugeot']);
    $peugeotVehicles = Vehicle::factory()->count(2)->for($peugeot)->create();
    Vehicle::factory()->for(Brand::factory()->create(['slug' => 'renault']))->create();

    $this->getJson('/api/vehicles?brand=peugeot')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.*.id', $peugeotVehicles->sortBy(['model', 'id'])->pluck('id')->values()->all());
});

it('returns no vehicle for an unknown brand', function () {
    Vehicle::factory()->create();

    $this->getJson('/api/vehicles?brand=unknown')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

it('paginates the vehicles', function () {
    Vehicle::factory()->count(VehicleController::PER_PAGE + 1)->create();

    $this->getJson('/api/vehicles')
        ->assertOk()
        ->assertJsonCount(VehicleController::PER_PAGE, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.per_page', VehicleController::PER_PAGE)
        ->assertJsonPath('meta.total', VehicleController::PER_PAGE + 1);

    $this->getJson('/api/vehicles?page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

it('keeps the brand filter in the pagination links', function () {
    Vehicle::factory()
        ->count(VehicleController::PER_PAGE + 1)
        ->for(Brand::factory()->create(['slug' => 'peugeot']))
        ->create();

    $this->getJson('/api/vehicles?brand=peugeot')
        ->assertJsonPath('links.next', url('/api/vehicles?brand=peugeot&page=2'));
});

it('sorts the vehicles by brand name then model', function () {
    $renault = Brand::factory()->create(['name' => 'Renault']);
    $citroen = Brand::factory()->create(['name' => 'Citroën']);
    Vehicle::factory()->for($renault)->create(['model' => 'CAPTUR']);
    Vehicle::factory()->for($citroen)->create(['model' => 'C5']);
    Vehicle::factory()->for($renault)->create(['model' => 'AUSTRAL']);
    Vehicle::factory()->for($citroen)->create(['model' => 'C3']);

    $this->getJson('/api/vehicles')
        ->assertJsonPath('data.*.model', ['C3', 'C5', 'AUSTRAL', 'CAPTUR']);
});

it('rejects invalid query parameters', function (array $query, string $field) {
    $this->getJson('/api/vehicles?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'page below one' => [['page' => 0], 'page'],
    'page not a number' => [['page' => 'two'], 'page'],
    'brand not a string' => [['brand' => ['peugeot']], 'brand'],
    'brand too long' => [['brand' => str_repeat('a', 256)], 'brand'],
]);

it('rate limits the API per client', function () {
    for ($attempt = 0; $attempt < 120; $attempt++) {
        $this->getJson('/api/vehicles')->assertOk();
    }

    $this->getJson('/api/vehicles')->assertTooManyRequests();
});
