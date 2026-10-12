<?php

namespace Database\Factories;

use App\Enums\Energy;
use App\Enums\Gearbox;
use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'external_id' => fake()->unique()->numerify('GLI########'),
            'brand_id' => Brand::factory(),
            'model' => fake()->word(),
            'version' => fake()->sentence(3),
            'year' => fake()->numberBetween(2015, 2026),
            'mileage' => fake()->numberBetween(0, 150_000),
            'energy' => fake()->randomElement(Energy::cases()),
            'gearbox' => fake()->randomElement(Gearbox::cases()),
            'price' => fake()->numberBetween(5_000, 80_000) * 100,
            'photo_url' => fake()->imageUrl(),
            'raw' => [],
            'synced_at' => now(),
        ];
    }
}
