<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Events;

use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\EventStatus;
use App\Shared\Enums\RoleEnum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrgEventManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => 'Database\Seeders\RoleSeeder']);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    private function createOrgWithRoles(): array
    {
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org-'.fake()->uuid(),
            'is_active' => true,
        ]);

        $eventManager = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($eventManager->id);
        $eventManager->assignRole(RoleEnum::EventManager->name);
        $eventManager->switchOrganization($org);
        $eventManager->unsetRelation('roles');

        return [$org, $eventManager];
    }

    private function createEvent(Organization $org, array $overrides = []): Event
    {
        $event = Event::create(array_merge([
            'organization_id' => $org->id,
            'title' => 'Org Event',
            'slug' => 'org-event-'.fake()->uuid(),
            'status' => EventStatus::Draft,
        ], $overrides));

        $event->sessions()->create([
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addHours(3),
            'capacity' => 100,
        ]);

        return $event;
    }

    #[Test]
    public function event_manager_can_view_org_events_index(): void
    {
        [$org, $eventManager] = $this->createOrgWithRoles();
        $this->createEvent($org);

        $response = $this->actingAs($eventManager)->get(route('org.events.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Org/Events/Index')->has('events', 1));
    }

    #[Test]
    public function event_manager_index_only_returns_own_organization_events(): void
    {
        [$orgA, $eventManager] = $this->createOrgWithRoles();

        $orgB = Organization::create([
            'name' => 'Other Org',
            'slug' => 'other-org-'.fake()->uuid(),
            'is_active' => true,
        ]);
        $this->createEvent($orgB);

        $response = $this->actingAs($eventManager)->get(route('org.events.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Org/Events/Index')->has('events', 0));
    }

    #[Test]
    public function event_manager_can_open_create_page(): void
    {
        [$org, $eventManager] = $this->createOrgWithRoles();

        $response = $this->actingAs($eventManager)->get(route('org.events.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Org/Events/Create'));
    }

    #[Test]
    public function event_manager_can_open_edit_page_for_own_org_event(): void
    {
        [$org, $eventManager] = $this->createOrgWithRoles();
        $event = $this->createEvent($org);

        $response = $this->actingAs($eventManager)->get(route('org.events.edit', $event));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Org/Events/Edit'));
    }

    #[Test]
    public function event_manager_can_publish_and_unpublish_event(): void
    {
        [$org, $eventManager] = $this->createOrgWithRoles();
        $event = $this->createEvent($org);

        $publish = $this->actingAs($eventManager)->post(
            route('org.events.toggle-status', $event),
            ['status' => 'published'],
        );

        $publish->assertRedirect(route('org.events.index'));
        $this->assertEquals(EventStatus::Published, $event->fresh()->status);

        $unpublish = $this->actingAs($eventManager)->post(
            route('org.events.toggle-status', $event),
            ['status' => 'draft'],
        );

        $unpublish->assertRedirect(route('org.events.index'));
        $this->assertEquals(EventStatus::Draft, $event->fresh()->status);
    }

    #[Test]
    public function customer_cannot_access_org_events_module(): void
    {
        $customer = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $customer->assignRole(RoleEnum::Customer->name);

        $this->actingAs($customer)->get(route('org.events.index'))->assertStatus(403);
        $this->actingAs($customer)->get(route('org.events.create'))->assertStatus(403);
    }

    #[Test]
    public function customer_cannot_toggle_event_status(): void
    {
        [$org, $eventManager] = $this->createOrgWithRoles();
        $event = $this->createEvent($org);

        $customer = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $customer->assignRole(RoleEnum::Customer->name);

        $this->actingAs($customer)->post(
            route('org.events.toggle-status', $event),
            ['status' => 'published'],
        )->assertStatus(403);
    }

    #[Test]
    public function anonymous_user_is_redirected_to_login(): void
    {
        $this->get(route('org.events.index'))->assertRedirect(route('login'));
    }
}
