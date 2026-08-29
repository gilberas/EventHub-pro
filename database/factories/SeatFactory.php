<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Venues\Models\Row;
use App\Domain\Venues\Models\Seat;
use App\Shared\Enums\SeatType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Seat> */
class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        $row = fake()->numberBetween(0, 25);
        $col = fake()->numberBetween(0, 30);

        return [
            'row_id' => Row::factory(),
            'seat_number' => fake()->unique()->numberBetween(1, 500),
            'type' => fake()->randomElement(SeatType::cases()),
            'row_position' => $row,
            'col_position' => $col,
            'x_coord' => $col * 40.0,
            'y_coord' => $row * 40.0,
            'is_active' => true,
        ];
    }
}
