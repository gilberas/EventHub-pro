<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\RoleEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => 'Database\Seeders\RoleSeeder']);
    }

    private function assignRole(User $user, RoleEnum $role): void
    {
        $roleName = $role->name;

        if ($role->isPlatformRole()) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            $user->assignRole($roleName);
        } else {
            $org = Organization::create([
                'name' => 'Test Org',
                'slug' => 'test-org-'.fake()->uuid(),
                'is_active' => true,
            ]);
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $user->assignRole($roleName);
        }

        $user->refresh();
    }

    /** @return list<array{role: RoleEnum, route: string, expectedStatus: int}> */
    public static function dashboardAccessProvider(): array
    {
        return [
            'Customer can access dashboard' => [
                'role' => RoleEnum::Customer,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'SuperAdministrator can access dashboard' => [
                'role' => RoleEnum::SuperAdministrator,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'OrganizationOwner can access dashboard' => [
                'role' => RoleEnum::OrganizationOwner,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'EventManager can access dashboard' => [
                'role' => RoleEnum::EventManager,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'FinanceManager can access dashboard' => [
                'role' => RoleEnum::FinanceManager,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'SupportAgent can access dashboard' => [
                'role' => RoleEnum::SupportAgent,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'TicketScanner can access dashboard' => [
                'role' => RoleEnum::TicketScanner,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'OrganizationAdmin can access dashboard' => [
                'role' => RoleEnum::OrganizationAdmin,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'PlatformAdmin can access dashboard' => [
                'role' => RoleEnum::PlatformAdmin,
                'route' => 'dashboard',
                'expectedStatus' => 200,
            ],
            'Customer can access profile' => [
                'role' => RoleEnum::Customer,
                'route' => 'profile.edit',
                'expectedStatus' => 200,
            ],
            'SuperAdmin can access profile' => [
                'role' => RoleEnum::SuperAdministrator,
                'route' => 'profile.edit',
                'expectedStatus' => 200,
            ],
        ];
    }

    #[Test]
    #[DataProvider('dashboardAccessProvider')]
    public function test_role_can_access_expected_routes(RoleEnum $role, string $route, int $expectedStatus): void
    {
        $user = User::factory()->create();
        $this->assignRole($user, $role);

        $response = $this->actingAs($user)->get(route($route));

        $response->assertStatus($expectedStatus);
    }

    #[Test]
    public function test_user_without_roles_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(403);
    }
}
