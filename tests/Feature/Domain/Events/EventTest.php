<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Events;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\WaitingListEntry;
use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\EventStatus;
use App\Shared\Enums\RoleEnum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => 'Database\Seeders\RoleSeeder']);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    private function createOrgWithEventManager(): array
    {
        $org = Organization::create([
            'name' => 'Test Events Org',
            'slug' => 'test-events-'.fake()->uuid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($user->id);
        $user->assignRole(RoleEnum::EventManager->name);
        $user->switchOrganization($org);

        return [$org, $user];
    }

    private function createPublishedEvent(Organization $org, array $overrides = []): Event
    {
        $event = Event::create(array_merge([
            'organization_id' => $org->id,
            'title' => 'Test Event',
            'slug' => 'test-event-'.fake()->uuid(),
            'description' => 'A test event description.',
            'category' => 'Technology',
            'status' => EventStatus::Published,
            'is_featured' => false,
        ], $overrides));

        $event->sessions()->create([
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addHours(3),
            'capacity' => 100,
        ]);

        return $event;
    }

    // Recurring event session generation
    #[Test]
    public function recurring_session_generates_correct_number_of_occurrences(): void
    {
        $dates = Event::expandRecurrenceRule('FREQ=WEEKLY;INTERVAL=1', now(), now()->addWeeks(5));

        $this->assertCount(6, $dates); // start date + 5 weekly occurrences

        for ($i = 0; $i < count($dates); $i++) {
            $this->assertEquals($i * 7, $dates[0]->diffInDays($dates[$i]));
        }
    }

    #[Test]
    public function recurring_session_generates_daily_occurrences(): void
    {
        $dates = Event::expandRecurrenceRule('FREQ=DAILY;INTERVAL=2', now(), now()->addDays(10));

        $this->assertCount(6, $dates); // day 0, 2, 4, 6, 8, 10

        for ($i = 0; $i < count($dates); $i++) {
            $this->assertEquals($i * 2, $dates[0]->diffInDays($dates[$i]));
        }
    }

    #[Test]
    public function recurring_session_generates_monthly_occurrences(): void
    {
        $dates = Event::expandRecurrenceRule('FREQ=MONTHLY', now(), now()->addMonths(4));

        $this->assertCount(5, $dates);
    }

    #[Test]
    public function create_event_with_recurring_sessions(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->unsetRelation('roles');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'title' => 'Weekly Workshop Series',
            'description' => 'A weekly workshop series running for 4 weeks.',
            'category' => 'Technology',
            'organization_id' => $org->id,
            'sessions' => [
                [
                    'start_date' => now()->addWeek()->format('Y-m-d H:i:s'),
                    'end_date' => now()->addWeek()->addHours(2)->format('Y-m-d H:i:s'),
                    'capacity' => 50,
                    'recurrence_rule' => 'FREQ=WEEKLY;INTERVAL=1',
                ],
            ],
        ]);

        $response->assertRedirect();
        $event = Event::where('slug', 'weekly-workshop-series')->first();
        $this->assertNotNull($event);
        $this->assertGreaterThanOrEqual(4, $event->sessions()->count()); // template + recurrences
    }

    // Draft events not visible publicly
    #[Test]
    public function draft_event_returns_404_for_public_show(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org, [
            'title' => 'Draft Event Test',
            'status' => EventStatus::Draft,
        ]);

        $response = $this->get(route('events.show', $event->slug));

        $response->assertStatus(404);
    }

    #[Test]
    public function published_event_is_visible_publicly(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org);

        $response = $this->get(route('events.show', $event->slug));

        $response->assertStatus(200);
    }

    #[Test]
    public function cancelled_event_returns_404_for_public_show(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org, [
            'title' => 'Cancelled Public',
            'status' => EventStatus::Cancelled,
        ]);

        $response = $this->get(route('events.show', $event->slug));

        $response->assertStatus(404);
    }

    #[Test]
    public function home_page_shows_featured_and_trending_events(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $this->createPublishedEvent($org, [
            'title' => 'Featured Event',
            'is_featured' => true,
            'trending_score' => 90.0,
        ]);
        $this->createPublishedEvent($org, [
            'title' => 'Trending Event Only',
            'is_featured' => false,
            'trending_score' => 85.0,
        ]);
        $this->createPublishedEvent($org, [
            'title' => 'Regular Event',
            'is_featured' => false,
            'trending_score' => null,
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('featuredEvents', 1)
            ->has('trendingEvents', 2)
        );
    }

    // Cross-tenant isolation
    #[Test]
    public function event_manager_cannot_edit_event_from_another_organization(): void
    {
        [$orgA, $managerA] = $this->createOrgWithEventManager();

        $orgB = Organization::create([
            'name' => 'Other Org',
            'slug' => 'other-'.fake()->uuid(),
            'is_active' => true,
        ]);

        $eventB = $this->createPublishedEvent($orgB);

        $response = $this->actingAs($managerA)->put(
            route('events.update', $eventB),
            ['title' => 'Hacked Title'],
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function event_manager_can_create_and_update_own_org_events(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole(RoleEnum::EventManager->name));

        $response = $this->actingAs($user)->post(route('events.store'), [
            'title' => 'My New Event',
            'description' => 'Created by event manager.',
            'category' => 'Music',
            'sessions' => [
                [
                    'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
                    'end_date' => now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                    'capacity' => 200,
                ],
            ],
        ]);

        $response->assertRedirect();
        $event = Event::where('title', 'My New Event')->first();
        $this->assertNotNull($event);

        $updateResponse = $this->actingAs($user)->put(
            route('events.update', $event),
            ['description' => 'Updated description.'],
        );

        $updateResponse->assertRedirect();
        $this->assertEquals('Updated description.', $event->fresh()->description);
    }

    #[Test]
    public function customer_cannot_create_events(): void
    {
        $customer = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $customer->assignRole(RoleEnum::Customer->name);

        $response = $this->actingAs($customer)->post(route('events.store'), [
            'title' => 'Customer Event',
            'description' => 'Should not be allowed.',
        ]);

        $response->assertStatus(403);
    }

    // Waiting list
    #[Test]
    public function user_can_join_waiting_list(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org);

        $customer = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $customer->assignRole(RoleEnum::Customer->name);

        $response = $this->actingAs($customer)->post(
            route('events.waiting-list.join', $event),
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('waiting_list_entries', [
            'event_id' => $event->id,
            'user_id' => $customer->id,
            'notified_at' => null,
        ]);
    }

    #[Test]
    public function user_can_leave_waiting_list(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org);

        $customer = User::factory()->create();
        WaitingListEntry::create([
            'event_id' => $event->id,
            'user_id' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->delete(
            route('events.waiting-list.leave', $event),
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('waiting_list_entries', [
            'event_id' => $event->id,
            'user_id' => $customer->id,
        ]);
    }

    #[Test]
    public function waiting_list_prevents_duplicate_entries(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org);

        $customer = User::factory()->create();

        WaitingListEntry::create([
            'event_id' => $event->id,
            'user_id' => $customer->id,
        ]);

        // Second attempt should not create duplicate due to unique constraint
        $this->actingAs($customer)->post(route('events.waiting-list.join', $event));
        $this->assertEquals(1, WaitingListEntry::where('event_id', $event->id)->count());
    }

    // Age restriction and refund policy
    #[Test]
    public function event_can_have_age_restriction_and_refund_policy(): void
    {
        [$org, $user] = $this->createOrgWithEventManager();
        $event = $this->createPublishedEvent($org, [
            'age_restriction' => 21,
            'refund_policy_days' => 7,
            'refund_policy_percentage' => 50.00,
        ]);

        $this->assertEquals(21, $event->age_restriction);
        $this->assertEquals(7, $event->refund_policy_days);
        $this->assertEquals(50.00, $event->refund_policy_percentage);
    }
}
