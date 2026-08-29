<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Venues\Models\Hall;
use App\Domain\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Hall> */
class HallFactory extends Factory
{
    protected $model = Hall::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'name' => fake()->word().' Hall',
            'description' => fake()->sentence(),
            'capacity' => fake()->numberBetween(50, 5000),
        ];
    }
}
