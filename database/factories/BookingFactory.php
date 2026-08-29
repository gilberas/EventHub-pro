<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Events\Models\EventSession;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Booking> */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_session_id' => EventSession::factory(),
            'reference' => Booking::generateReference(),
            'status' => BookingStatus::PendingPayment,
            'subtotal' => 100,
            'fees' => 10,
            'total' => 110,
            'currency' => 'USD',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => BookingStatus::Confirmed, 'paid_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BookingStatus::Cancelled]);
    }
}
