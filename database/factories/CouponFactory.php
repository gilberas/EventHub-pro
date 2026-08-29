<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Models\Coupon;
use App\Shared\Enums\CouponType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => strtoupper(fake()->unique()->bothify('COUPON-?????')),
            'type' => fake()->randomElement([CouponType::Percentage, CouponType::Fixed]),
            'value' => fake()->randomFloat(2, 5, 100),
            'min_order_amount' => fake()->optional(0.3)->randomFloat(2, 20, 100),
            'max_discount' => null,
            'max_uses' => fake()->optional(0.5)->numberBetween(50, 500),
            'current_uses' => 0,
            'starts_at' => null,
            'expires_at' => fake()->optional(0.3)->dateTimeBetween('+1 month', '+6 months'),
            'is_active' => true,
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Percentage,
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 10,
            'current_uses' => 10,
        ]);
    }
}
