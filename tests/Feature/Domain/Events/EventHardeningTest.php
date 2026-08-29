<?php

declare(strict_types=1);

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Events\Services\EventService;
use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\EventStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('deletes an event', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $service = app(EventService::class);

    $service->delete($event);
    expect(Event::find($event->id))->toBeNull();
});

it('returns published featured events', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id, 'status' => EventStatus::Published, 'is_featured' => true]);
    EventSession::factory()->create(['event_id' => $event->id]);

    $service = app(EventService::class);
    $featured = $service->getFeaturedEvents();
    expect($featured)->toHaveCount(1);
});

it('filters draft events from featured', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id, 'status' => EventStatus::Draft, 'is_featured' => true]);
    EventSession::factory()->create(['event_id' => $event->id]);

    $service = app(EventService::class);
    $featured = $service->getFeaturedEvents();
    expect($featured)->toHaveCount(0);
});

it('returns trending events', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id, 'status' => EventStatus::Published, 'trending_score' => 10]);
    EventSession::factory()->create(['event_id' => $event->id]);

    $service = app(EventService::class);
    $trending = $service->getTrendingEvents();
    expect($trending)->toHaveCount(1);
});

it('filters draft events from trending', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id, 'status' => EventStatus::Draft, 'trending_score' => 5]);
    EventSession::factory()->create(['event_id' => $event->id]);

    $service = app(EventService::class);
    $trending = $service->getTrendingEvents();
    expect($trending)->toHaveCount(0);
});

it('returns only published events via getPublicEvents', function () {
    $org = Organization::factory()->create();
    $publishedEvent = Event::factory()->create(['organization_id' => $org->id, 'status' => EventStatus::Published]);
    $draftEvent = Event::factory()->create(['organization_id' => $org->id, 'status' => EventStatus::Draft]);
    EventSession::factory()->create(['event_id' => $publishedEvent->id]);
    EventSession::factory()->create(['event_id' => $draftEvent->id]);

    $service = app(EventService::class);
    $public = $service->getPublicEvents();
    expect($public)->toHaveCount(1);
});

it('searches events by keyword', function () {
    $org = Organization::factory()->create();
    Event::factory()->create(['organization_id' => $org->id, 'title' => 'Music Festival', 'status' => EventStatus::Published]);
    Event::factory()->create(['organization_id' => $org->id, 'title' => 'Art Workshop', 'status' => EventStatus::Published]);

    $service = app(EventService::class);
    $results = $service->searchEvents(['q' => 'Music']);
    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Music Festival');
});

it('checks waiting list status', function () {
    $org = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $user = User::factory()->create();
    $service = app(EventService::class);

    expect($service->isOnWaitingList($event, $user))->toBeFalse();
    $service->joinWaitingList($event, $user);
    expect($service->isOnWaitingList($event, $user))->toBeTrue();
    $service->leaveWaitingList($event, $user);
    expect($service->isOnWaitingList($event, $user))->toBeFalse();
});
