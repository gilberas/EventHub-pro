<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\EventSession;
use App\Shared\Enums\TicketMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketType> */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'event_session_id' => EventSession::factory(),
            'name' => fake()->randomElement(['General Admission', 'VIP', 'Standard', 'Premium']),
            'mode' => fake()->randomElement(TicketMode::cases()),
            'price' => fake()->randomFloat(2, 10, 200),
            'quantity_available' => fake()->numberBetween(50, 500),
            'max_per_order' => 10,
            'sort_order' => 0,
        ];
    }

    public function reserved(): static
    {
        return $this->state(['mode' => TicketMode::Reserved, 'name' => 'Reserved Seating', 'quantity_available' => null]);
    }

    public function ga(): static
    {
        return $this->state(['mode' => TicketMode::GeneralAdmission, 'name' => 'General Admission']);
    }
}
