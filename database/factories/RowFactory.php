<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Venues\Models\Row;
use App\Domain\Venues\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Row> */
class RowFactory extends Factory
{
    protected $model = Row::class;

    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'label' => fake()->randomLetter(),
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
