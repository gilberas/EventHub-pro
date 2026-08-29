<?php

declare(strict_types=1);

use App\Domain\Analytics\Services\PlatformBiService;
use App\Domain\Analytics\Services\ReportingService;
use App\Domain\Bookings\Models\Booking;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Shared\Enums\BookingStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ---- ReportingService remaining methods ----

it('returns refund summary', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    Booking::factory()->count(2)->create([
        'event_session_id' => $session->id,
        'status' => BookingStatus::Refunded,
        'total' => 50,
    ]);
    Booking::factory()->create([
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'total' => 100,
    ]);

    $report = app(ReportingService::class)->refundSummary($org);
    expect($report['total_refunds'])->toBe(100.0);
    expect($report['refund_count'])->toBe(2);
});

it('returns org overview', function () {
    $org = Organization::factory()->create();
    Event::factory()->create(['organization_id' => $org->id]);

    $overview = app(ReportingService::class)->orgOverview($org);
    expect($overview)->toHaveKeys(['total_events', 'active_events', 'total_venues', 'total_ticket_types']);
    expect($overview['total_events'])->toBe(1);
});

it('returns sales by event', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    Booking::factory()->create([
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'total' => 200,
    ]);

    $sales = app(ReportingService::class)->salesByEvent($org);
    expect($sales)->toHaveKeys(['total_revenue', 'booking_count', 'fees', 'refunds']);
    expect($sales['total_revenue'])->toBe(200.0);
    expect($sales['booking_count'])->toBe(1);
});

// ---- PlatformBiService remaining methods ----

it('returns platform org summary', function () {
    Organization::factory()->count(3)->create(['is_active' => true]);
    Organization::factory()->create(['is_active' => false]);

    $summary = app(PlatformBiService::class)->platformOrgSummary();
    expect($summary['total_orgs'])->toBe(4);
    expect($summary['active_orgs'])->toBe(3);
    expect($summary['suspended_orgs'])->toBe(1);
});

it('returns monthly platform revenue', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    Booking::factory()->create([
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'total' => 500,
        'created_at' => now(),
    ]);

    $revenue = app(PlatformBiService::class)->monthlyPlatformRevenue();
    expect($revenue)->toHaveCount(6);
    $thisMonth = now()->format('Y-m');
    expect($revenue[$thisMonth])->toBe(500.0);
});
