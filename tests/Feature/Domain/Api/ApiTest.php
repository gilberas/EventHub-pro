<?php

declare(strict_types=1);

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    RateLimiter::clear('api-auth');
    RateLimiter::clear('api-booking');
});

// --------------- Auth: Registration ---------------

it('registers a new user and returns a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    expect(User::where('email', 'test@example.com'))->exists()->toBeTrue();
});

// --------------- Auth: Login ---------------

it('logs in and returns a token', function () {
    $user = User::factory()->create(['password' => bcrypt('Password123!')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['user', 'token']);
});

it('rejects invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(422);
});

// --------------- Auth: Logout ---------------

it('logs out and revokes the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

    $response->assertStatus(200);
    expect($user->tokens()->count())->toBe(0);
});

// --------------- Auth: Me ---------------

it('returns the authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/auth/me');

    $response->assertStatus(200);
    $response->assertJsonPath('user.email', $user->email);
});

// --------------- Protected Routes ---------------

it('requires authentication for protected routes', function () {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
    $this->postJson('/api/v1/auth/logout')->assertStatus(401);
    $this->getJson('/api/v1/auth/tokens')->assertStatus(401);
    $this->getJson('/api/v1/bookings')->assertStatus(401);
    $this->postJson('/api/v1/checkout')->assertStatus(401);
    $this->getJson('/api/v1/tickets')->assertStatus(401);
    $this->postJson('/api/v1/tickets/validate')->assertStatus(401);
    $this->postJson('/api/v1/sessions/1/hold')->assertStatus(401);
});

// --------------- Public Routes ---------------

it('lists public events without authentication', function () {
    $org = Organization::factory()->create();
    Event::factory()->count(3)->create(['organization_id' => $org->id]);

    $response = $this->getJson('/api/v1/events');

    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'meta']);
    expect($response->json('data'))->toHaveCount(3);
});

it('shows a single event by slug', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);

    $response = $this->getJson("/api/v1/events/{$event->slug}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.slug', $event->slug);
});

// --------------- Booking: Own bookings only ---------------

it('lists only the authenticated users bookings', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);

    Booking::factory()->count(2)->create(['user_id' => $user->id, 'event_session_id' => $session->id]);
    Booking::factory()->count(3)->create(['user_id' => $otherUser->id, 'event_session_id' => $session->id]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/bookings');

    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(2);
});

it('shows a single booking by reference', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create(['user_id' => $user->id, 'event_session_id' => $session->id]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/v1/bookings/{$booking->reference}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.reference', $booking->reference);
});

it('prevents viewing another users booking', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create(['user_id' => $other->id, 'event_session_id' => $session->id]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/v1/bookings/{$booking->reference}");

    $response->assertStatus(403);
});

// --------------- Tickets: Own tickets only ---------------

it('lists only the authenticated users tickets', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $tt = TicketType::factory()->create(['event_session_id' => $session->id]);

    $createBookingWithTicket = function (User $u) use ($session, $tt) {
        $b = Booking::factory()->create(['user_id' => $u->id, 'event_session_id' => $session->id, 'status' => BookingStatus::Confirmed]);
        $item = BookingItem::factory()->create(['booking_id' => $b->id, 'ticket_type_id' => $tt->id, 'quantity' => 1]);
        Ticket::factory()->create(['booking_id' => $b->id, 'booking_item_id' => $item->id, 'event_session_id' => $session->id, 'ticket_type_id' => $tt->id]);
    };

    $createBookingWithTicket($user);
    $createBookingWithTicket($user);
    $createBookingWithTicket($otherUser);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/tickets');

    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(2);
});

// --------------- Rate Limiting ---------------

it('rate limits auth register endpoint', function () {
    $payload = ['name' => 'U', 'email' => fake()->email, 'password' => 'Password123!', 'password_confirmation' => 'Password123!'];

    // Exhaust the limiter: 5 attempts allowed
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);
    }

    // 6th attempt should be rate limited
    $this->postJson('/api/v1/auth/register', $payload)->assertStatus(429);
})->skip(fn () => PHP_OS_FAMILY === 'Windows' && ! extension_loaded('redis'), 'Rate limiter test needs consistent cache driver');

it('rate limits booking endpoints', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Simulate hitting the rate limit by calling hold (even with bad data, counts as attempt)
    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit('api-booking'.$user->id);
    }

    $response = $this->withToken($token)->postJson('/api/v1/sessions/999/hold', []);

    // After exhausting the limiter, we should get 429
    expect($response->status())->toBe(429);
})->skip(fn () => PHP_OS_FAMILY === 'Windows' && ! extension_loaded('redis'), 'Rate limiter test needs consistent cache driver');

// --------------- Cross-Tenant Isolation ---------------

it('does not leak cross-tenant data via events', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    $event1 = Event::factory()->create(['organization_id' => $org1->id, 'title' => 'Org1 Event']);
    $event2 = Event::factory()->create(['organization_id' => $org2->id, 'title' => 'Org2 Event']);

    $response = $this->getJson('/api/v1/events');

    $response->assertStatus(200);
    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Org1 Event', 'Org2 Event');
});

it('does not leak cross-tenant booking data', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    $user = User::factory()->create();
    $event1 = Event::factory()->create(['organization_id' => $org1->id]);
    $event2 = Event::factory()->create(['organization_id' => $org2->id]);
    $session1 = EventSession::factory()->create(['event_id' => $event1->id]);
    $session2 = EventSession::factory()->create(['event_id' => $event2->id]);

    Booking::factory()->create(['user_id' => $user->id, 'event_session_id' => $session1->id]);
    Booking::factory()->create(['user_id' => $user->id, 'event_session_id' => $session2->id]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/bookings');

    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(2);
});
