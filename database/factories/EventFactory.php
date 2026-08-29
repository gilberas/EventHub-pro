<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use App\Shared\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'organization_id' => Organization::factory(),
            'title' => $title,
            'slug' => Event::generateSlug($title),
            'description' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['Technology', 'Music', 'Business', 'Design', 'Wellness', 'Food', 'Sports', 'Art']),
            'tags' => fake()->randomElements(['workshop', 'conference', 'networking', 'live', 'online', 'premium', 'free'], 2),
            'status' => EventStatus::Published,
            'age_restriction' => null,
            'is_featured' => false,
            'trending_score' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Draft,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Published,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'trending_score' => fake()->randomFloat(1, 1, 100),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Completed,
        ]);
    }

    public function withAgeRestriction(int $minAge = 18): static
    {
        return $this->state(fn (array $attributes) => [
            'age_restriction' => $minAge,
        ]);
    }

    public function withRefundPolicy(int $days = 14, float $percentage = 100.0): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_policy_days' => $days,
            'refund_policy_percentage' => $percentage,
        ]);
    }
}
