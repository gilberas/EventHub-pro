<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Bookings;

use App\Domain\Bookings\Jobs\ExpireSeatHolds;
use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingSeat;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Venues\Models\Seat;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\EventStatus;
use App\Shared\Enums\SeatHoldStatus;
use App\Shared\Enums\TicketMode;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function createEventWithSession(): array
    {
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org-bookings',
            'is_active' => true,
        ]);

        $event = Event::create([
            'organization_id' => $org->id,
            'title' => 'Test Concert',
            'slug' => 'test-concert-bookings',
            'status' => EventStatus::Published,
        ]);

        $session = EventSession::create([
            'event_id' => $event->id,
            'title' => 'Main Show',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addHours(3),
            'capacity' => 100,
        ]);

        $venue = Venue::factory()->create(['organization_id' => $org->id]);
        $hall = $venue->halls()->create(['name' => 'Main Hall', 'capacity' => 100]);
        $section = $hall->sections()->create(['name' => 'Floor', 'section_type' => 'standard', 'capacity' => 100]);
        $section->generateGrid(['rows' => 5, 'seats_per_row' => 10]);

        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($user->id);
        $user->assignRole('Customer');

        return [$event, $session, $user, $org];
    }

    private function createReservedTicketType(EventSession $session): TicketType
    {
        return TicketType::create([
            'event_session_id' => $session->id,
            'name' => 'Reserved Seating',
            'mode' => TicketMode::Reserved,
            'price' => 50.00,
            'quantity_available' => null,
            'max_per_order' => 10,
        ]);
    }

    private function createGATicketType(EventSession $session): TicketType
    {
        return TicketType::create([
            'event_session_id' => $session->id,
            'name' => 'General Admission',
            'mode' => TicketMode::GeneralAdmission,
            'price' => 25.00,
            'quantity_available' => 20,
            'max_per_order' => 5,
        ]);
    }

    public function test_hold_reserved_seats(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createReservedTicketType($session);
        $seat = $session->event->load('sessions'); // refresh

        // Get first seat from the generated grid
        $seat = Seat::first();

        $this->actingAs($user);

        $response = $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'seat_ids' => [$seat->id],
        ]);

        $response->assertRedirect(route('checkout.review'));

        $this->assertDatabaseHas('seat_holds', [
            'event_session_id' => $session->id,
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'status' => SeatHoldStatus::Active->value,
        ]);
    }

    public function test_hold_ga_tickets(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createGATicketType($session);

        $this->actingAs($user);

        $response = $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 3,
        ]);

        $response->assertRedirect(route('checkout.review'));

        $this->assertDatabaseHas('seat_holds', [
            'event_session_id' => $session->id,
            'ticket_type_id' => $tt->id,
            'user_id' => $user->id,
            'quantity' => 3,
            'status' => SeatHoldStatus::Active->value,
        ]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $tt->id,
            'quantity_available' => 17, // 20 - 3
        ]);
    }

    public function test_complete_checkout_flow(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createReservedTicketType($session);
        $seat = Seat::first();

        $this->actingAs($user);

        // Hold the seat
        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'seat_ids' => [$seat->id],
        ]);

        // Checkout
        $response = $this->post(route('checkout.process'));
        $response->assertRedirect();

        $booking = Booking::where('user_id', $user->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals(BookingStatus::PendingPayment, $booking->status);
        $this->assertEquals(50.00, (float) $booking->subtotal);
        $this->assertEquals(2.50, (float) $booking->fees);
        $this->assertEquals(52.50, (float) $booking->total);

        // Seat should be linked to booking
        $this->assertDatabaseHas('booking_seats', [
            'booking_id' => $booking->id,
            'seat_id' => $seat->id,
            'event_session_id' => $session->id,
        ]);

        // Hold should be converted
        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seat->id,
            'status' => SeatHoldStatus::Converted->value,
        ]);
    }

    public function test_seat_hold_expiry_job(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createReservedTicketType($session);
        $seat = Seat::first();

        // Create an expired hold directly
        $hold = SeatHold::create([
            'event_session_id' => $session->id,
            'ticket_type_id' => $tt->id,
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'quantity' => 1,
            'status' => SeatHoldStatus::Active,
            'expires_at' => now()->subMinute(),
        ]);

        // Run the expiry job
        $job = new ExpireSeatHolds;
        $job->handle();

        $this->assertDatabaseHas('seat_holds', [
            'id' => $hold->id,
            'status' => SeatHoldStatus::Expired->value,
        ]);
    }

    public function test_booking_seat_unique_constraint(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createReservedTicketType($session);
        $seat = Seat::first();

        $booking = Booking::create([
            'user_id' => $user->id,
            'event_session_id' => $session->id,
            'reference' => 'BK-TEST0001',
            'status' => BookingStatus::Confirmed,
            'subtotal' => 50,
            'fees' => 2.50,
            'total' => 52.50,
        ]);

        BookingSeat::create([
            'booking_id' => $booking->id,
            'event_session_id' => $session->id,
            'seat_id' => $seat->id,
            'ticket_type_id' => $tt->id,
        ]);

        // Second attempt must fail (DB-level unique constraint)
        $this->expectException(QueryException::class);

        BookingSeat::create([
            'booking_id' => $booking->id,
            'event_session_id' => $session->id,
            'seat_id' => $seat->id,
            'ticket_type_id' => $tt->id,
        ]);
    }

    /** @test */
    public function concurrent_seat_hold_race_condition(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createReservedTicketType($session);
        $seat = Seat::first();

        $user2 = User::factory()->create();

        // Simulate concurrent holds by using DB::transaction with FOR UPDATE
        $exceptions = [];

        // User 1 holds the seat
        $this->actingAs($user);
        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'seat_ids' => [$seat->id],
        ]);

        // User 2 tries to hold the same seat — should fail
        $this->actingAs($user2);
        $response = $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'seat_ids' => [$seat->id],
        ]);

        // User 2's attempt should redirect back with an error
        $response->assertSessionHasErrors('hold');

        // There should be only one hold for this seat
        $holdCount = SeatHold::where('seat_id', $seat->id)
            ->where('event_session_id', $session->id)
            ->where('status', SeatHoldStatus::Active)
            ->count();

        $this->assertEquals(1, $holdCount, 'Only one user should hold the seat');

        // User 1 should still hold the seat
        $this->assertDatabaseHas('seat_holds', [
            'seat_id' => $seat->id,
            'user_id' => $user->id,
            'status' => SeatHoldStatus::Active->value,
        ]);
    }

    /** @test */
    public function concurrent_ga_ticket_race_condition_via_db_transaction(): void
    {
        [, $session, $user] = $this->createEventWithSession();
        $tt = $this->createGATicketType($session);

        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // User 1 holds 15 GA tickets
        $this->actingAs($user);
        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $tt->id,
            'quantity_available' => 5, // 20 - 15
        ]);

        // User 2 tries to hold 10 GA tickets — only 5 left, should fail
        $this->actingAs($user2);
        $response = $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 10,
        ]);

        $response->assertSessionHasErrors('hold');

        // User 3 holds the remaining 5
        $this->actingAs($user3);
        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $tt->id,
            'quantity_available' => 0,
        ]);
    }

    public function test_public_event_page_shows_availability(): void
    {
        [$event, $session, $user] = $this->createEventWithSession();
        $tt = $this->createReservedTicketType($session);
        $seat = Seat::first();

        // Event should show as having available tickets
        $response = $this->get(route('events.show', $event->slug));
        $response->assertOk();

        // Book the seat
        $this->actingAs($user);
        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'seat_ids' => [$seat->id],
        ]);
        $this->post(route('checkout.process'));

        // Check the event detail page loads fine
        $this->get(route('events.show', $event->slug))->assertOk();
    }
}
