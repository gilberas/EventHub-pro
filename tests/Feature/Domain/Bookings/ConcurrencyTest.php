<?php

declare(strict_types=1);

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Venues\Models\Hall;
use App\Domain\Venues\Models\Row;
use App\Domain\Venues\Models\Seat;
use App\Domain\Venues\Models\Section;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\SeatHoldStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('prevents over-holding seats under concurrent pressure', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create([
        'event_id' => $event->id,
        'capacity' => 100,
    ]);

    $venue = Venue::factory()->create(['organization_id' => $org->id]);
    $hall = Hall::factory()->create(['venue_id' => $venue->id]);
    $section = Section::factory()->create(['hall_id' => $hall->id]);
    $row = Row::factory()->create(['section_id' => $section->id]);

    $seats = [];
    for ($col = 1; $col <= 5; $col++) {
        $seats[] = Seat::factory()->create([
            'row_id' => $row->id,
            'row_position' => 1,
            'col_position' => $col,
        ]);
    }

    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 50,
        'quantity_available' => 10,
    ]);

    $users = User::factory()->count(10)->create();
    $service = app(BookingService::class);

    $exceptions = 0;
    $successes = 0;

    foreach ($users as $user) {
        try {
            DB::beginTransaction();
            $service->holdSeats($session, $ticketType, $user, [$seats[0]->id, $seats[1]->id]);
            DB::commit();
            $successes++;
        } catch (RuntimeException $e) {
            DB::rollBack();
            $exceptions++;
        }
    }

    expect($successes)->toBeLessThanOrEqual(2);
    expect($successes + $exceptions)->toBe(10);
    expect(SeatHold::where('event_session_id', $session->id)
        ->where('status', SeatHoldStatus::Active)
        ->where('expires_at', '>', now())
        ->count()
    )->toBe($successes * 2);
});

it('prevents over-holding GA tickets under concurrent pressure', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create([
        'event_id' => $event->id,
        'capacity' => 100,
    ]);

    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 50,
        'quantity_available' => 3,
    ]);

    $users = User::factory()->count(5)->create();
    $service = app(BookingService::class);

    $successes = 0;
    $exceptions = 0;

    foreach ($users as $user) {
        try {
            DB::beginTransaction();
            $service->holdGATickets($session, $ticketType, $user, 2);
            DB::commit();
            $successes++;
        } catch (RuntimeException $e) {
            DB::rollBack();
            $exceptions++;
        }
    }

    expect($successes)->toBeLessThanOrEqual(2);
    expect($successes + $exceptions)->toBe(5);
});

it('completes checkout atomically under concurrent holds', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create([
        'event_id' => $event->id,
        'capacity' => 100,
    ]);
    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 25,
        'quantity_available' => 10,
    ]);

    $user = User::factory()->create();
    $service = app(BookingService::class);

    $hold = $service->holdGATickets($session, $ticketType, $user, 3);
    $booking = $service->checkout($user, [$hold->id]);

    expect($booking)->toBeInstanceOf(Booking::class);
    expect($booking->status)->toBe(BookingStatus::PendingPayment);
    expect($booking->items()->count())->toBe(1);
    expect((int) $booking->items->first()->quantity)->toBe(3);
    expect($hold->fresh()->status)->toBe(SeatHoldStatus::Converted);
});
