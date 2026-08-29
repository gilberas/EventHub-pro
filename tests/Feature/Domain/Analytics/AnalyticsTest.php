<?php

declare(strict_types=1);

use App\Domain\Analytics\Services\PlatformBiService;
use App\Domain\Analytics\Services\ReportingService;
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
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --------------- Reporting Service Tests ---------------

it('revenue summary aggregates correctly for seeded bookings', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $tt = TicketType::factory()->create(['event_session_id' => $session->id, 'price' => 50]);

    Booking::factory()->count(3)->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'subtotal' => 100,
        'fees' => 5,
        'total' => 105,
    ]);

    $report = app(ReportingService::class)->revenueSummary($org);

    expect($report['total_revenue'])->toBe(315.0);
    expect($report['booking_count'])->toBe(3);
    expect($report['avg_order_value'])->toBe(105.0);
    expect($report['fees_collected'])->toBe(15.0);
});

it('revenue summary excludes pending payment bookings', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $tt = TicketType::factory()->create(['event_session_id' => $session->id, 'price' => 50]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'total' => 105,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::PendingPayment,
        'total' => 200,
    ]);

    $report = app(ReportingService::class)->revenueSummary($org);

    expect($report['total_revenue'])->toBe(105.0);
    expect($report['booking_count'])->toBe(1);
});

it('attendance summary counts checked-in tickets', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $tt = TicketType::factory()->create(['event_session_id' => $session->id, 'price' => 50]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $tt->id,
        'quantity' => 2,
    ]);

    Ticket::factory()->create([
        'booking_id' => $booking->id,
        'booking_item_id' => $item->id,
        'event_session_id' => $session->id,
        'status' => 'used',
        'checked_in_at' => now(),
    ]);

    Ticket::factory()->create([
        'booking_id' => $booking->id,
        'booking_item_id' => $item->id,
        'event_session_id' => $session->id,
        'status' => 'active',
    ]);

    $attendance = app(ReportingService::class)->attendanceSummary($org);

    expect($attendance['total'])->toBe(2);
    expect($attendance['checked_in'])->toBe(1);
    expect($attendance['rate'])->toBe(50.0);
});

it('popular events returns events ordered by revenue', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();

    $event1 = Event::factory()->create(['organization_id' => $org->id, 'title' => 'Top Event']);
    $session1 = EventSession::factory()->create(['event_id' => $event1->id]);
    $tt1 = TicketType::factory()->create(['event_session_id' => $session1->id, 'price' => 100]);
    Booking::factory()->count(2)->create([
        'user_id' => $user->id,
        'event_session_id' => $session1->id,
        'status' => BookingStatus::Confirmed,
        'total' => 200,
    ]);

    $event2 = Event::factory()->create(['organization_id' => $org->id, 'title' => 'Lower Event']);
    $session2 = EventSession::factory()->create(['event_id' => $event2->id]);
    $tt2 = TicketType::factory()->create(['event_session_id' => $session2->id, 'price' => 50]);
    Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session2->id,
        'status' => BookingStatus::Confirmed,
        'total' => 50,
    ]);

    $popular = app(ReportingService::class)->popularEvents($org, 5);

    expect($popular)->toHaveCount(2);
    expect($popular[0]['title'])->toBe('Top Event');
    expect($popular[0]['total_revenue'])->toBeGreaterThan($popular[1]['total_revenue']);
});

it('monthly revenue returns structured data for last 6 months', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $tt = TicketType::factory()->create(['event_session_id' => $session->id, 'price' => 50]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'total' => 100,
        'created_at' => now(),
    ]);

    $monthly = app(ReportingService::class)->monthlyRevenue($org, 6);

    $currentMonth = now()->format('Y-m');
    expect($monthly)->toHaveCount(6);
    expect($monthly[$currentMonth])->toBe(100.0);
});

it('customer growth counts org-associated users', function () {
    $org = Organization::factory()->create();
    $users = User::factory()->count(3)->create();

    foreach ($users as $u) {
        DB::table('organization_user')->insert([
            'organization_id' => $org->id,
            'user_id' => $u->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $growth = app(ReportingService::class)->customerGrowth($org);

    expect($growth['total_customers'])->toBe(3);
});

// --------------- Platform BI Tests ---------------

it('platform revenue aggregates across all organizations', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    $user = User::factory()->create();

    foreach ([$org1, $org2] as $org) {
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $session = EventSession::factory()->create(['event_id' => $event->id]);
        $tt = TicketType::factory()->create(['event_session_id' => $session->id, 'price' => 100]);
        Booking::factory()->create([
            'user_id' => $user->id,
            'event_session_id' => $session->id,
            'status' => BookingStatus::Confirmed,
            'total' => 200,
        ]);
    }

    $bi = app(PlatformBiService::class);
    $revenue = $bi->platformRevenue();

    expect($revenue['total_revenue'])->toBe(400.0);
    expect($revenue['total_bookings'])->toBe(2);
});

it('organizer leaderboard ranks by revenue', function () {
    $org1 = Organization::factory()->create(['name' => 'Top Org']);
    $org2 = Organization::factory()->create(['name' => 'Lower Org']);
    $user = User::factory()->create();

    $event1 = Event::factory()->create(['organization_id' => $org1->id]);
    $session1 = EventSession::factory()->create(['event_id' => $event1->id]);
    $tt1 = TicketType::factory()->create(['event_session_id' => $session1->id, 'price' => 100]);
    Booking::factory()->count(3)->create([
        'user_id' => $user->id,
        'event_session_id' => $session1->id,
        'status' => BookingStatus::Confirmed,
        'total' => 300,
    ]);

    $event2 = Event::factory()->create(['organization_id' => $org2->id]);
    $session2 = EventSession::factory()->create(['event_id' => $event2->id]);
    $tt2 = TicketType::factory()->create(['event_session_id' => $session2->id, 'price' => 100]);
    Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session2->id,
        'status' => BookingStatus::Confirmed,
        'total' => 100,
    ]);

    $board = app(PlatformBiService::class)->organizerLeaderboard(10);

    expect($board[0]['name'])->toBe('Top Org');
    expect((float) $board[0]['total_revenue'])->toBeGreaterThan((float) $board[1]['total_revenue']);
});

it('platform customer growth counts all users', function () {
    User::factory()->count(5)->create();

    $growth = app(PlatformBiService::class)->platformCustomerGrowth();

    expect($growth['total_platform_users'])->toBe(5);
});

// --------------- Admin Route Access Control ---------------

it('admin organization list requires PlatformAdmin or SuperAdmin role', function () {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');

    $this->actingAs($customer)
        ->get(route('admin.organizations'))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole('PlatformAdmin');

    $this->actingAs($admin)
        ->get(route('admin.organizations'))
        ->assertOk();
});

it('admin organization list is accessible by SuperAdmin', function () {
    $sa = User::factory()->create();
    $sa->assignRole('SuperAdministrator');

    $this->actingAs($sa)
        ->get(route('admin.organizations'))
        ->assertOk();
});

it('admin users list requires PlatformAdmin or SuperAdmin', function () {
    $user = User::factory()->create();
    $user->assignRole('EventManager');

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();
});

it('system health route is gated by admin role', function () {
    $user = User::factory()->create();
    $user->assignRole('OrganizationOwner');

    $this->actingAs($user)
        ->get(route('admin.system.health'))
        ->assertForbidden();
});

it('audit log route requires PlatformAdmin or higher', function () {
    $scanner = User::factory()->create();
    $scanner->assignRole('TicketScanner');

    $this->actingAs($scanner)
        ->get(route('admin.audit-log'))
        ->assertForbidden();
});
