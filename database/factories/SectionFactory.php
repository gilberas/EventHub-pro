<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Venues\Models\Hall;
use App\Domain\Venues\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Section> */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'name' => fake()->randomElement(['Floor', 'Balcony', 'Mezzanine', 'Orchestra']),
            'section_type' => fake()->randomElement(['standard', 'vip', 'premium', 'ga']),
            'color' => fake()->hexColor(),
            'capacity' => fake()->numberBetween(20, 500),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
