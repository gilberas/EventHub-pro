<?php

declare(strict_types=1);

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\SeatHoldStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $this->session = EventSession::factory()->create(['event_id' => $event->id, 'capacity' => 50]);
    $this->service = app(BookingService::class);
    $this->user = User::factory()->create();
});

// ---- getEventCapacity ----

it('returns capacity breakdown when no holds or bookings', function () {
    $caps = $this->service->getEventCapacity($this->session);
    expect($caps['total_capacity'])->toBe(50);
    expect($caps['booked'])->toBe(0);
    expect($caps['held'])->toBe(0);
    expect($caps['available'])->toBe(50);
});

it('returns capacity breakdown with active holds', function () {
    $tt = TicketType::factory()->create(['event_session_id' => $this->session->id]);
    SeatHold::create([
        'event_session_id' => $this->session->id,
        'ticket_type_id' => $tt->id,
        'seat_id' => null,
        'user_id' => $this->user->id,
        'quantity' => 1,
        'status' => SeatHoldStatus::Active,
        'expires_at' => now()->addMinutes(5),
    ]);

    $caps = $this->service->getEventCapacity($this->session);
    expect($caps['held'])->toBe(1);
    expect($caps['available'])->toBe(49);
});

it('ignores expired holds in capacity', function () {
    $tt = TicketType::factory()->create(['event_session_id' => $this->session->id]);
    SeatHold::create([
        'event_session_id' => $this->session->id,
        'ticket_type_id' => $tt->id,
        'seat_id' => null,
        'user_id' => $this->user->id,
        'quantity' => 1,
        'status' => SeatHoldStatus::Active,
        'expires_at' => now()->subMinute(),
    ]);

    $caps = $this->service->getEventCapacity($this->session);
    expect($caps['held'])->toBe(0);
});

// ---- isSoldOut ----

it('returns false when tickets are available', function () {
    expect($this->service->isSoldOut($this->session))->toBeFalse();
});

it('returns true when capacity is exhausted', function () {
    $this->session->update(['capacity' => 0]);
    expect($this->service->isSoldOut($this->session))->toBeTrue();
});

// ---- cancelBooking ----

it('cancels a pending payment booking', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'event_session_id' => $this->session->id,
        'status' => BookingStatus::PendingPayment,
    ]);
    $this->service->cancelBooking($booking);
    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});

it('throws when cancelling a confirmed booking', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'event_session_id' => $this->session->id,
        'status' => BookingStatus::Confirmed,
    ]);
    $this->service->cancelBooking($booking);
})->throws(RuntimeException::class, 'Confirmed bookings cannot be cancelled online');

// ---- processRefund ----

it('throws on refunding a pending payment booking', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'event_session_id' => $this->session->id,
        'status' => BookingStatus::PendingPayment,
    ]);
    $gateway = Mockery::mock(PaymentGateway::class);
    $this->service->processRefund($booking, $gateway, 10.0);
})->throws(RuntimeException::class, 'Cannot refund a booking that was never charged.');
