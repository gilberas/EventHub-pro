<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Events\Models\EventSession;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'booking_item_id' => BookingItem::factory(),
            'event_session_id' => EventSession::factory(),
            'ticket_type_id' => null,
            'seat_id' => null,
            'ticket_number' => Ticket::generateTicketNumber(),
            'qr_payload' => 'placeholder_payload',
            'status' => 'active',
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_in_at' => now(),
            'checked_in_by_user_id' => User::factory(),
            'status' => 'used',
        ]);
    }
}
