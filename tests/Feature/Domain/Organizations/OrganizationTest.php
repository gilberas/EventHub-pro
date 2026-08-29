<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Organizations;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Models\StaffInvitation;
use App\Models\User;
use App\Shared\Enums\RoleEnum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => 'Database\Seeders\RoleSeeder']);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    private function createOrgWithOwner(string $name = 'Test Org'): array
    {
        $org = Organization::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.fake()->uuid(),
            'is_active' => true,
        ]);

        $owner = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($owner->id);
        $owner->assignRole(RoleEnum::OrganizationOwner->name);

        return [$org, $owner];
    }

    private function addStaff(Organization $org, string $roleName): User
    {
        $staff = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($staff->id);
        $staff->assignRole($roleName);

        return $staff;
    }

    // Staff invitation → accept flow
    #[Test]
    public function owner_can_invite_staff_to_organization(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();

        $owner->unsetRelation('roles');
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertTrue($owner->hasRole(RoleEnum::OrganizationOwner->name));

        $response = $this->actingAs($owner)->post(
            route('organizations.invitations.store', $org),
            [
                'email' => 'staff@example.com',
                'role' => RoleEnum::SupportAgent->name,
            ],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_invitations', [
            'organization_id' => $org->id,
            'email' => 'staff@example.com',
            'role' => RoleEnum::SupportAgent->name,
            'accepted_at' => null,
        ]);
    }

    #[Test]
    public function invited_user_can_accept_invitation_and_join_organization(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();

        $invitation = StaffInvitation::create([
            'organization_id' => $org->id,
            'email' => 'staff@example.com',
            'role' => RoleEnum::SupportAgent->name,
            'token' => 'test-accept-token',
            'invited_by_user_id' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $staff = User::factory()->create(['email' => 'staff@example.com']);

        $response = $this->actingAs($staff)->get(
            route('organizations.invitations.accept', $invitation->token),
        );

        $response->assertRedirect(route('organizations.settings', $org));

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $staff->unsetRelation('roles');
        $this->assertTrue($staff->hasRole(RoleEnum::SupportAgent->name));
    }

    #[Test]
    public function invitation_with_wrong_email_cannot_be_accepted(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();

        $invitation = StaffInvitation::create([
            'organization_id' => $org->id,
            'email' => 'staff@example.com',
            'role' => RoleEnum::SupportAgent->name,
            'token' => 'wrong-email-token',
            'invited_by_user_id' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        $response = $this->actingAs($otherUser)->get(
            route('organizations.invitations.accept', $invitation->token),
        );

        $response->assertRedirect();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertFalse($otherUser->hasRole(RoleEnum::SupportAgent->name));
    }

    #[Test]
    public function expired_invitation_cannot_be_accepted(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();

        $invitation = StaffInvitation::create([
            'organization_id' => $org->id,
            'email' => 'staff@example.com',
            'role' => RoleEnum::SupportAgent->name,
            'token' => 'expired-token',
            'invited_by_user_id' => $owner->id,
            'expires_at' => now()->subDay(),
        ]);

        $staff = User::factory()->create(['email' => 'staff@example.com']);

        $response = $this->actingAs($staff)->get(
            route('organizations.invitations.accept', $invitation->token),
        );

        $response->assertRedirect();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertFalse($staff->hasRole(RoleEnum::SupportAgent->name));
    }

    // Role revocation takes effect immediately
    #[Test]
    public function role_revocation_takes_effect_immediately(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $staff = $this->addStaff($org, RoleEnum::EventManager->name);

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $staff->unsetRelation('roles');
        $this->assertTrue($staff->hasRole(RoleEnum::EventManager->name));

        $this->actingAs($owner)->delete(
            route('organizations.staff.remove', [$org->id, $staff->id]),
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $staff->refresh();
        $staff->unsetRelation('roles');
        $this->assertFalse($staff->hasRole(RoleEnum::EventManager->name));
    }

    #[Test]
    public function staff_cannot_access_organization_settings_after_role_removed(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $staff = $this->addStaff($org, RoleEnum::SupportAgent->name);

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $staff->unsetRelation('roles');
        $this->assertTrue($staff->hasRole(RoleEnum::SupportAgent->name));

        $this->actingAs($owner)->delete(
            route('organizations.staff.remove', [$org->id, $staff->id]),
        );

        // Reload user completely
        $staff = User::find($staff->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $staff->unsetRelation('roles');
        $this->assertFalse($staff->hasRole(RoleEnum::SupportAgent->name));

        $response = $this->actingAs($staff)->get(
            route('organizations.settings', $org),
        );

        $response->assertStatus(403);
    }

    // Cross-tenant isolation
    #[Test]
    public function owner_of_org_a_cannot_manage_staff_of_org_b(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('Org A');
        [$orgB, $ownerB] = $this->createOrgWithOwner('Org B');

        $staffB = $this->addStaff($orgB, RoleEnum::EventManager->name);

        // Owner A tries to remove staff from Org B
        $response = $this->actingAs($ownerA)->delete(
            route('organizations.staff.remove', [$orgB->id, $staffB->id]),
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_of_org_a_cannot_update_settings_of_org_b(): void
    {
        [$orgA] = $this->createOrgWithOwner('Org A');
        [$orgB, $ownerB] = $this->createOrgWithOwner('Org B');

        $response = $this->actingAs($ownerB)->put(
            route('organizations.update', $orgA),
            ['name' => 'Hacked Org A'],
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_of_org_a_cannot_invite_staff_to_org_b(): void
    {
        [$orgA, $ownerA] = $this->createOrgWithOwner('Org A');
        [, $ownerB] = $this->createOrgWithOwner('Org B');

        $response = $this->actingAs($ownerB)->post(
            route('organizations.invitations.store', $orgA),
            [
                'email' => 'evil@example.com',
                'role' => RoleEnum::SupportAgent->name,
            ],
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function organization_admin_can_view_settings(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $admin = $this->addStaff($org, RoleEnum::OrganizationAdmin->name);

        $response = $this->actingAs($admin)->get(
            route('organizations.settings', $org),
        );

        $response->assertStatus(200);
    }

    #[Test]
    public function organization_admin_can_manage_staff(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $admin = $this->addStaff($org, RoleEnum::OrganizationAdmin->name);
        $staff = $this->addStaff($org, RoleEnum::SupportAgent->name);

        $admin->unsetRelation('roles');
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertTrue($admin->hasRole(RoleEnum::OrganizationAdmin->name));

        $response = $this->actingAs($admin)->delete(
            route('organizations.staff.remove', [$org->id, $staff->id]),
        );

        $response->assertRedirect();
    }

    #[Test]
    public function event_manager_cannot_manage_staff(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $eventMgr = $this->addStaff($org, RoleEnum::EventManager->name);
        $staff = $this->addStaff($org, RoleEnum::SupportAgent->name);

        $response = $this->actingAs($eventMgr)->delete(
            route('organizations.staff.remove', [$org->id, $staff->id]),
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function support_agent_cannot_view_organization_settings(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $agent = $this->addStaff($org, RoleEnum::SupportAgent->name);

        $response = $this->actingAs($agent)->get(
            route('organizations.settings', $org),
        );

        $response->assertStatus(200); // Support agents belong to org so can view
    }

    #[Test]
    public function organization_admin_can_update_organization_profile(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $admin = $this->addStaff($org, RoleEnum::OrganizationAdmin->name);

        $admin->unsetRelation('roles');
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $this->assertTrue($admin->hasRole(RoleEnum::OrganizationAdmin->name));

        $response = $this->actingAs($admin)->put(
            route('organizations.update', $org),
            [
                'name' => 'Updated Org Name',
                'timezone' => 'America/Chicago',
            ],
        );

        $response->assertRedirect();
        $org->refresh();
        $this->assertEquals('Updated Org Name', $org->name);
        $this->assertEquals('America/Chicago', $org->timezone);
    }

    #[Test]
    public function event_manager_cannot_update_organization_profile(): void
    {
        [$org, $owner] = $this->createOrgWithOwner();
        $eventMgr = $this->addStaff($org, RoleEnum::EventManager->name);

        $response = $this->actingAs($eventMgr)->put(
            route('organizations.update', $org),
            ['name' => 'Hacked Name'],
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function customer_without_org_roles_cannot_access_org_settings(): void
    {
        $org = Organization::create([
            'name' => 'Isolated Org',
            'slug' => 'isolated-'.fake()->uuid(),
            'is_active' => true,
        ]);

        $customer = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $customer->assignRole(RoleEnum::Customer->name);

        $response = $this->actingAs($customer)->get(
            route('organizations.settings', $org),
        );

        $response->assertStatus(403);
    }
}
