<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Bookings\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BookingItem> */
class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'ticket_type_id' => TicketType::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->randomFloat(2, 10, 200),
            'subtotal' => fn (array $attrs) => $attrs['quantity'] * $attrs['unit_price'],
        ];
    }
}
