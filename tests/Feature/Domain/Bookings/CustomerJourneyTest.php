<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Bookings;

use App\Domain\Bookings\Jobs\ExpireSeatHolds;
use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Venues\Models\Hall;
use App\Domain\Venues\Models\Seat;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\EventStatus;
use App\Shared\Enums\SeatHoldStatus;
use App\Shared\Enums\TicketMode;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function createOrganization(): Organization
    {
        return Organization::create([
            'name' => 'CJ Org',
            'slug' => 'cj-org-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function createCustomerUser(Organization $org): User
    {
        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($user->id);
        $user->assignRole('Customer');

        return $user;
    }

    private function createEvent(Organization $org, bool $reserved): array
    {
        $event = Event::create([
            'organization_id' => $org->id,
            'title' => $reserved ? 'Journey Theatre' : 'Journey Concert',
            'slug' => ($reserved ? 'journey-theatre-' : 'journey-concert-').uniqid(),
            'status' => EventStatus::Published,
        ]);

        $session = EventSession::create([
            'event_id' => $event->id,
            'title' => 'Main Show',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addHours(3),
            'capacity' => 100,
        ]);

        $ticketType = $reserved
            ? TicketType::create([
                'event_session_id' => $session->id,
                'name' => 'Reserved Seating',
                'mode' => TicketMode::Reserved,
                'price' => 50,
                'quantity_available' => null,
                'max_per_order' => 4,
            ])
            : TicketType::create([
                'event_session_id' => $session->id,
                'name' => 'General Admission',
                'mode' => TicketMode::GeneralAdmission,
                'price' => 25,
                'quantity_available' => 20,
                'max_per_order' => 5,
            ]);

        if ($reserved) {
            $venue = Venue::firstOrCreate(
                ['slug' => 'cj-venue-'.$org->id],
                ['organization_id' => $org->id, 'name' => 'CJ Venue', 'slug' => 'cj-venue-'.$org->id, 'is_active' => true],
            );
            $hall = Hall::create(['venue_id' => $venue->id, 'name' => 'Main Hall', 'capacity' => 100]);
            $section = $hall->sections()->create(['name' => 'Floor', 'section_type' => 'standard', 'color' => '#333', 'capacity' => 100, 'sort_order' => 0]);
            $section->generateGrid(['rows' => 3, 'seats_per_row' => 4]);
            $session->update(['venue_id' => $venue->id]);
        }

        return [$event, $session, $ticketType];
    }

    public function test_a_guest_must_login_before_booking(): void
    {
        [$event, $session, $tt] = $this->createEvent($this->createOrganization(), reserved: false);

        $this->get(route('events.show', $event->slug))->assertOk();

        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 2,
        ])->assertRedirect(route('login'));
    }

    public function test_a_ga_full_journey_until_ticket(): void
    {
        $org = $this->createOrganization();
        [, $session, $tt] = $this->createEvent($org, reserved: false);
        $user = $this->createCustomerUser($org);

        $this->actingAs($user);

        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 2,
        ])->assertRedirect(route('checkout.review'));

        $this->assertDatabaseHas('ticket_types', [
            'id' => $tt->id,
            'quantity_available' => 18,
        ]);

        $this->get(route('checkout.review'))->assertOk();

        $hold = SeatHold::where('user_id', $user->id)->firstOrFail();

        $this->post(route('checkout.pay'), [
            'hold_ids' => [$hold->id],
            'gateway' => 'mock',
            'payment' => [],
        ])->assertRedirect();

        $booking = Booking::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(BookingStatus::Confirmed->value, $booking->status->value);
        $this->assertEquals(50.00, (float) $booking->subtotal);
        $this->assertEquals(2.50, (float) $booking->fees);
        $this->assertEquals(52.50, (float) $booking->total);

        $this->assertDatabaseHas('payment_transactions', [
            'payable_id' => $booking->id,
            'gateway' => 'mock',
            'status' => 'succeeded',
        ]);

        $tickets = Ticket::where('booking_id', $booking->id)->get();
        $this->assertCount(2, $tickets);
        $tickets->each(fn (Ticket $t) => $this->assertNotSame('', $t->qr_payload));

        $this->get(route('tickets.index'))->assertOk();
        $this->get(route('bookings.show', $booking->reference))->assertOk();
        $this->get(route('tickets.qr', $tickets->first()->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_b_reserved_seating_full_journey(): void
    {
        $org = $this->createOrganization();
        [$event, $session, $tt] = $this->createEvent($org, reserved: true);
        $user = $this->createCustomerUser($org);

        $this->get(route('events.sessions.book', [$event->slug, $session->id]))->assertOk();

        $seats = Seat::take(2)->get();
        $this->actingAs($user);

        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'seat_ids' => $seats->pluck('id')->all(),
        ])->assertRedirect(route('checkout.review'));

        $this->assertDatabaseHas('seat_holds', [
            'event_session_id' => $session->id,
            'seat_id' => $seats->first()->id,
            'user_id' => $user->id,
            'status' => SeatHoldStatus::Active->value,
        ]);

        $holds = SeatHold::where('user_id', $user->id)->pluck('id')->all();

        $this->post(route('checkout.pay'), [
            'hold_ids' => $holds,
            'gateway' => 'mock',
            'payment' => [],
        ])->assertRedirect();

        $booking = Booking::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(BookingStatus::Confirmed->value, $booking->status->value);

        foreach ($seats as $seat) {
            $this->assertDatabaseHas('booking_seats', [
                'booking_id' => $booking->id,
                'seat_id' => $seat->id,
                'event_session_id' => $session->id,
            ]);
        }

        $tickets = Ticket::where('booking_id', $booking->id)->get();
        $this->assertCount(2, $tickets);
        $this->assertNotNull($tickets[0]->seat_id);
        $this->assertNotNull($tickets[1]->seat_id);
    }

    public function test_c_unauthorized_access_is_blocked(): void
    {
        $org = $this->createOrganization();
        [, $session, $tt] = $this->createEvent($org, reserved: false);
        $owner = $this->createCustomerUser($org);
        $intruder = $this->createCustomerUser($org);

        $this->actingAs($owner);
        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 1,
        ])->assertRedirect(route('checkout.review'));

        $hold = SeatHold::where('user_id', $owner->id)->firstOrFail();
        $this->post(route('checkout.pay'), [
            'hold_ids' => [$hold->id],
            'gateway' => 'mock',
            'payment' => [],
        ])->assertRedirect();

        $booking = Booking::where('user_id', $owner->id)->firstOrFail();
        $ticket = Ticket::where('booking_id', $booking->id)->firstOrFail();

        $this->actingAs($intruder);
        $this->get(route('bookings.show', $booking->reference))->assertForbidden();
        $this->get(route('tickets.qr', $ticket->id))->assertForbidden();

        $this->get(route('tickets.index'))->assertOk();
        $this->assertTrue(Ticket::where('booking_id', $booking->id)->exists());

        $this->get(route('org.events.index'))->assertForbidden();
    }

    public function test_ga_hold_expiry_restores_inventory(): void
    {
        $org = $this->createOrganization();
        [, $session, $tt] = $this->createEvent($org, reserved: false);
        $user = $this->createCustomerUser($org);

        app(BookingService::class)->holdGATickets($session, $tt, $user, 4);

        SeatHold::where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $tt->id,
            'quantity_available' => 16,
        ]);

        (new ExpireSeatHolds)->handle();

        $this->assertDatabaseHas('ticket_types', [
            'id' => $tt->id,
            'quantity_available' => 20,
        ]);
    }

    public function test_max_per_order_is_enforced_server_side(): void
    {
        $org = $this->createOrganization();
        [, $session, $tt] = $this->createEvent($org, reserved: false);
        $user = $this->createCustomerUser($org);

        $this->actingAs($user);

        $this->post(route('sessions.hold', $session->id), [
            'ticket_type_id' => $tt->id,
            'quantity' => 6,
        ])->assertSessionHasErrors('hold');

        $this->assertDatabaseMissing('seat_holds', ['user_id' => $user->id]);
    }
}
