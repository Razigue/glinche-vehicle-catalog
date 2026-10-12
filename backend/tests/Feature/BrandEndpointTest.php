<?php

use App\Models\Brand;
use App\Models\Vehicle;

it('lists the brands that have vehicles with their vehicle count', function () {
    $peugeot = Brand::factory()->create(['name' => 'Peugeot', 'slug' => 'peugeot']);
    $citroen = Brand::factory()->create(['name' => 'Citroën', 'slug' => 'citroen']);
    Brand::factory()->create(['name' => 'Renault', 'slug' => 'renault']);
    Vehicle::factory()->count(2)->for($peugeot)->create();
    Vehicle::factory()->for($citroen)->create();

    $this->getJson('/api/brands')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                ['name' => 'Citroën', 'slug' => 'citroen', 'vehicles_count' => 1],
                ['name' => 'Peugeot', 'slug' => 'peugeot', 'vehicles_count' => 2],
            ],
        ]);
});
