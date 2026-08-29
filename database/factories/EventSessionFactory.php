<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Events\Models\EventSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSession>
 */
class EventSessionFactory extends Factory
{
    protected $model = EventSession::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'title' => null,
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+'.fake()->numberBetween(1, 4).' hours'),
            'location' => fake()->optional(0.7)->address(),
            'capacity' => fake()->optional(0.8)->numberBetween(10, 5000),
            'sort_order' => 0,
        ];
    }

    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    public function recurring(string $rrule, int $count = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence_rule' => $rrule,
        ]);
    }

    public function atCapacity(): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => 0,
        ]);
    }
}
