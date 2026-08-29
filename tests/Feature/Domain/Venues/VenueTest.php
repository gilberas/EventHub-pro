<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Venues;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function createOrgOwner(): array
    {
        $user = User::factory()->create();
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);
        $user->organizations()->attach($org);
        $user->switchOrganization($org);
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->unsetRelation('roles');
        $user->assignRole('OrganizationOwner');

        return [$user, $org];
    }

    public function test_owner_can_create_venue(): void
    {
        [$user, $org] = $this->createOrgOwner();

        $this->actingAs($user)
            ->post(route('venues.store'), [
                'name' => 'Main Stadium',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
            ])->assertRedirect();

        $this->assertDatabaseHas('venues', [
            'name' => 'Main Stadium',
            'organization_id' => $org->id,
        ]);
    }

    public function test_owner_can_view_venue(): void
    {
        [$user, $org] = $this->createOrgOwner();
        $venue = Venue::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->get(route('venues.show', $venue->slug))
            ->assertOk();
    }

    public function test_owner_can_update_venue(): void
    {
        [$user, $org] = $this->createOrgOwner();
        $venue = Venue::factory()->create(['organization_id' => $org->id, 'name' => 'Old Name']);

        $this->actingAs($user)
            ->patch(route('venues.update', $venue->slug), ['name' => 'Updated Venue'])
            ->assertRedirect();

        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'Updated Venue']);
    }

    public function test_owner_can_delete_venue(): void
    {
        [$user, $org] = $this->createOrgOwner();
        $venue = Venue::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->delete(route('venues.destroy', $venue->slug))
            ->assertRedirect();

        $this->assertSoftDeleted($venue);
    }

    public function test_cross_tenant_isolation(): void
    {
        [$user] = $this->createOrgOwner();
        $otherOrg = Organization::create(['name' => 'Other Org', 'slug' => 'other-org']);
        $venue = Venue::factory()->create(['organization_id' => $otherOrg->id]);

        $this->actingAs($user)
            ->patch(route('venues.update', $venue->slug), ['name' => 'Hacked'])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('venues.destroy', $venue->slug))
            ->assertForbidden();
    }

    public function test_can_create_venue_with_seat_layout(): void
    {
        [$user, $org] = $this->createOrgOwner();

        $this->actingAs($user)
            ->post(route('venues.store'), [
                'name' => 'Concert Hall',
                'layout' => [
                    [
                        'name' => 'Main Hall',
                        'sections' => [
                            ['name' => 'Floor', 'rows' => 5, 'seats_per_row' => 10, 'seat_type' => 'standard', 'color' => '#3b82f6'],
                            ['name' => 'Balcony', 'rows' => 3, 'seats_per_row' => 8, 'seat_type' => 'vip', 'color' => '#eab308'],
                        ],
                    ],
                ],
            ])->assertRedirect();

        $venue = Venue::where('name', 'Concert Hall')->first();
        $this->assertNotNull($venue);
        $venue->load('halls.sections.rows.seats');
        $this->assertCount(1, $venue->halls);
        $this->assertCount(2, $venue->halls->first()->sections);
        $this->assertCount(5, $venue->halls->first()->sections->first()->rows);
        $this->assertCount(10, $venue->halls->first()->sections->first()->rows->first()->seats);
    }

    public function test_can_duplicate_venue_with_layout(): void
    {
        [$user, $org] = $this->createOrgOwner();

        $venue = Venue::factory()->create(['organization_id' => $org->id, 'name' => 'Template Venue']);
        $hall = $venue->halls()->create(['name' => 'Main Hall', 'capacity' => 50]);
        $section = $hall->sections()->create(['name' => 'Floor', 'section_type' => 'standard', 'capacity' => 50]);
        $row = $section->rows()->create(['label' => 'A', 'sort_order' => 0]);
        $row->seats()->create(['seat_number' => 1, 'type' => 'standard', 'row_position' => 0, 'col_position' => 0]);
        $row->seats()->create(['seat_number' => 2, 'type' => 'standard', 'row_position' => 0, 'col_position' => 1]);

        $this->actingAs($user)
            ->post(route('venues.duplicate', $venue->slug))
            ->assertRedirect();

        $copy = Venue::where('name', 'Copy of Template Venue')->first();
        $this->assertNotNull($copy);
        $copy->load('halls.sections.rows.seats');
        $this->assertCount(1, $copy->halls);
        $this->assertCount(1, $copy->halls->first()->sections);
        $this->assertCount(1, $copy->halls->first()->sections->first()->rows);
        $this->assertCount(2, $copy->halls->first()->sections->first()->rows->first()->seats);
    }

    public function test_lists_venues(): void
    {
        [$user, $org] = $this->createOrgOwner();
        Venue::factory()->count(3)->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->get(route('venues.index'))
            ->assertOk();
    }

    public function test_guest_cannot_create_venue(): void
    {
        $this->post(route('venues.store'), ['name' => 'Test'])->assertRedirect(route('login'));
    }
}
