<?php

declare(strict_types=1);

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketScanLog;
use App\Domain\Tickets\Services\TicketService;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --------------- Ticket Generation ---------------

it('generates tickets for each booking item quantity', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id, 'capacity' => 100]);

    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 50,
        'quantity_available' => 10,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'total' => 100,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 3,
        'unit_price' => 50,
        'subtotal' => 150,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);

    expect($tickets)->toHaveCount(3);

    foreach ($tickets as $ticket) {
        expect($ticket->booking_id)->toBe($booking->id);
        expect($ticket->event_session_id)->toBe($session->id);
        expect($ticket->status)->toBe('active');
        expect($ticket->ticket_number)->toStartWith('TKT-');
        expect($ticket->qr_payload)->not->toBeEmpty();
    }
});

it('generates tickets for general admission bookings', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);

    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 25,
        'quantity_available' => 50,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 2,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);

    expect($tickets)->toHaveCount(2);
    expect($tickets[0]->seat_id)->toBeNull();
});

it('generates a valid QR payload with encrypted ticket data', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);

    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $payload = $ticket->qr_payload;
    expect($payload)->not->toBeEmpty();

    $decoded = base64_decode($payload, true);
    expect($decoded)->not->toBeFalse();

    $parts = explode('.', $decoded);
    expect($parts)->toHaveCount(2);

    $decrypted = Crypt::decryptString($parts[0]);
    $data = json_decode($decrypted, true);

    expect($data)->toHaveKeys(['ticket_id', 'session_id', 'nonce']);
    expect($data['ticket_id'])->toBe($ticket->id);
    expect($data['session_id'])->toBe($session->id);
});

// --------------- Scan Validation ---------------

it('validates a fresh scan successfully', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $result = $service->validateScan($ticket->qr_payload, $session, $scanner);

    expect($result['valid'])->toBeTrue();
    expect($result['ticket'])->not->toBeNull();
    expect($result['ticket']['checked_in_at'])->not->toBeNull();
});

it('rejects a duplicate scan of the same ticket', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $result1 = $service->validateScan($ticket->qr_payload, $session, $scanner);
    expect($result1['valid'])->toBeTrue();

    $result2 = $service->validateScan($ticket->qr_payload, $session, $scanner);
    expect($result2['valid'])->toBeFalse();
    expect($result2['error'])->toContain('already been used');
});

it('rejects a scan for the wrong event session', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session1 = EventSession::factory()->create(['event_id' => $event->id]);
    $session2 = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session1->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session1->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $result = $service->validateScan($ticket->qr_payload, $session2, $scanner);

    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('different event session');
});

it('rejects a tampered QR payload', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $decoded = base64_decode($ticket->qr_payload, true);
    $parts = explode('.', $decoded);

    $tamperedEncrypted = $parts[0];
    $tamperedEncrypted[-1] = $tamperedEncrypted[-1] === 'a' ? 'b' : 'a';

    $tamperedPayload = base64_encode($tamperedEncrypted.'.'.$parts[1]);

    $result = $service->validateScan($tamperedPayload, $session, $scanner);

    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('tampered');
});

it('rejects a payload with invalid signature', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $decoded = base64_decode($ticket->qr_payload, true);
    $parts = explode('.', $decoded);

    $wrongSignature = $parts[1];
    $wrongSignature[0] = $wrongSignature[0] === 'a' ? 'b' : 'a';

    $tamperedPayload = base64_encode($parts[0].'.'.$wrongSignature);

    $result = $service->validateScan($tamperedPayload, $session, $scanner);

    expect($result['valid'])->toBeFalse();
});

it('logs all scan attempts in ticket_scan_logs', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $service->validateScan($ticket->qr_payload, $session, $scanner);

    $logs = TicketScanLog::all();
    expect($logs)->toHaveCount(1);
    expect($logs[0]->result)->toBe('valid');
    expect($logs[0]->scanned_by_user_id)->toBe($scanner->id);

    $service->validateScan($ticket->qr_payload, $session, $scanner);

    $logs = TicketScanLog::all();
    expect($logs)->toHaveCount(2);
    expect($logs[1]->result)->toBe('already_used');
});

it('allows manual check-in via ticket number', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $result = $service->manualCheckIn($ticket, $scanner);

    expect($result['valid'])->toBeTrue();
    expect($ticket->fresh()->checked_in_at)->not->toBeNull();
    expect($ticket->fresh()->checked_in_by_user_id)->toBe($scanner->id);
});

it('rejects manual check-in for already checked-in ticket', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $scanner = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create(['event_session_id' => $session->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
    ]);

    $service = app(TicketService::class);
    $tickets = $service->generateTickets($booking);
    $ticket = $tickets[0];

    $service->manualCheckIn($ticket, $scanner);
    $result = $service->manualCheckIn($ticket->fresh(), $scanner);

    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('already used');
});
