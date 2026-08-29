<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Events\Models\WaitingListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitingListEntry>
 */
class WaitingListEntryFactory extends Factory
{
    protected $model = WaitingListEntry::class;

    public function definition(): array
    {
        return [];
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => now(),
        ]);
    }
}
