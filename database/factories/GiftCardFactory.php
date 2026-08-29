<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Models\GiftCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftCardFactory extends Factory
{
    protected $model = GiftCard::class;

    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 20, 500);

        return [
            'organization_id' => Organization::factory(),
            'issued_by_user_id' => User::factory(),
            'code' => strtoupper(fake()->unique()->bothify('GC-??????')),
            'original_balance' => $balance,
            'current_balance' => $balance,
            'expires_at' => fake()->optional(0.3)->dateTimeBetween('+3 months', '+12 months'),
            'is_active' => true,
        ];
    }

    public function partiallyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => 0,
        ]);
    }
}
