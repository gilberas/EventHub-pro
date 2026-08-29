<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Authorization;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\RefundRequest;
use App\Domain\Payments\Services\PaymentGatewayManager;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\EventStatus;
use App\Shared\Enums\RefundStatus;
use App\Shared\Enums\RoleEnum;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => 'Database\Seeders\RoleSeeder']);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    private function createOrg(string $name = 'Test Org'): Organization
    {
        return Organization::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->uuid(),
            'is_active' => true,
        ]);
    }

    private function orgUser(Organization $org, RoleEnum $role): User
    {
        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($user->id);
        $user->assignRole($role->name);
        $user->switchOrganization($org);
        $user->unsetRelation('roles');

        return $user;
    }

    private function platformUser(RoleEnum $role): User
    {
        $user = User::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $user->assignRole($role->name);

        return $user;
    }

    private function createSession(Organization $org): EventSession
    {
        $event = Event::create([
            'organization_id' => $org->id,
            'title' => 'Auth Event',
            'slug' => 'auth-event-'.fake()->uuid(),
            'status' => EventStatus::Published,
        ]);

        return $event->sessions()->create([
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addHours(3),
            'capacity' => 100,
        ]);
    }

    private function createRefund(Organization $org): RefundRequest
    {
        $session = $this->createSession($org);
        $booking = Booking::factory()->create([
            'event_session_id' => $session->id,
            'status' => BookingStatus::Confirmed,
            'total' => 100,
        ]);

        return RefundRequest::create([
            'booking_id' => $booking->id,
            'requested_by_user_id' => $booking->user_id,
            'amount' => 100,
            'reason' => 'Test refund',
            'status' => RefundStatus::Pending,
        ]);
    }

    // ---- Venue authorization ----

    #[Test]
    public function event_manager_can_create_venue_in_own_org(): void
    {
        $org = $this->createOrg();
        $manager = $this->orgUser($org, RoleEnum::EventManager);

        $this->actingAs($manager)
            ->post(route('venues.store'), ['name' => 'Own Hall', 'city' => 'Lisbon'])
            ->assertRedirect();

        $this->assertDatabaseHas('venues', ['name' => 'Own Hall', 'organization_id' => $org->id]);
    }

    #[Test]
    public function customer_cannot_create_venue(): void
    {
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($customer)
            ->post(route('venues.store'), ['name' => 'Hack Hall'])
            ->assertForbidden();
    }

    #[Test]
    public function cross_org_manager_cannot_modify_venue(): void
    {
        $orgA = $this->createOrg('Org A');
        $orgB = $this->createOrg('Org B');
        $managerB = $this->orgUser($orgB, RoleEnum::EventManager);
        $venue = Venue::factory()->create(['organization_id' => $orgA->id]);

        $this->actingAs($managerB)->patch(route('venues.update', $venue->slug), ['name' => 'Hacked'])->assertForbidden();
        $this->actingAs($managerB)->delete(route('venues.destroy', $venue->slug))->assertForbidden();
    }

    // ---- Refund management authorization ----

    #[Test]
    public function finance_manager_can_view_pending_refunds(): void
    {
        $org = $this->createOrg();
        $this->createRefund($org);
        $finance = $this->orgUser($org, RoleEnum::FinanceManager);

        $this->actingAs($finance)
            ->get(route('payments.pending-refunds'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Payments/PendingRefunds')->has('refunds.data', 1));
    }

    #[Test]
    public function pending_refunds_are_scoped_to_own_org(): void
    {
        $orgA = $this->createOrg('Org A');
        $orgB = $this->createOrg('Org B');
        $this->createRefund($orgA);
        $this->createRefund($orgB);
        $financeA = $this->orgUser($orgA, RoleEnum::FinanceManager);

        $this->actingAs($financeA)
            ->get(route('payments.pending-refunds'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Payments/PendingRefunds')->has('refunds.data', 1));
    }

    #[Test]
    public function customer_cannot_view_pending_refunds(): void
    {
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($customer)->get(route('payments.pending-refunds'))->assertForbidden();
    }

    #[Test]
    public function finance_manager_cannot_reject_other_org_refund(): void
    {
        $orgA = $this->createOrg('Org A');
        $orgB = $this->createOrg('Org B');
        $refundB = $this->createRefund($orgB);
        $financeA = $this->orgUser($orgA, RoleEnum::FinanceManager);

        $this->actingAs($financeA)
            ->from(route('payments.pending-refunds'))
            ->post(route('payments.refunds.reject', $refundB))
            ->assertForbidden();

        $this->assertEquals(RefundStatus::Pending, $refundB->fresh()->status);
    }

    #[Test]
    public function finance_manager_can_reject_own_org_refund(): void
    {
        app(PaymentGatewayManager::class)->register('stripe', fn () => \Mockery::mock(PaymentGateway::class));

        $org = $this->createOrg();
        $refund = $this->createRefund($org);
        $finance = $this->orgUser($org, RoleEnum::FinanceManager);

        $this->actingAs($finance)
            ->from(route('payments.pending-refunds'))
            ->post(route('payments.refunds.reject', $refund))
            ->assertRedirect();

        $this->assertEquals(RefundStatus::Rejected, $refund->fresh()->status);
    }

    // ---- Scanner authorization ----

    #[Test]
    public function ticket_scanner_can_open_scanner(): void
    {
        $org = $this->createOrg();
        $scanner = $this->orgUser($org, RoleEnum::TicketScanner);

        $this->actingAs($scanner)->get(route('scanner'))->assertOk();
    }

    #[Test]
    public function customer_cannot_access_scanner(): void
    {
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($customer)->get(route('scanner'))->assertForbidden();
        $this->actingAs($customer)->get(route('scanner.session-tickets', $this->createSession($this->createOrg())->id))->assertForbidden();
    }

    #[Test]
    public function scanner_session_tickets_are_org_scoped(): void
    {
        $orgA = $this->createOrg('Org A');
        $orgB = $this->createOrg('Org B');
        $scannerA = $this->orgUser($orgA, RoleEnum::TicketScanner);

        $ownSession = $this->createSession($orgA);
        $otherSession = $this->createSession($orgB);

        $this->actingAs($scannerA)->get(route('scanner.session-tickets', $ownSession))->assertOk();
        $this->actingAs($scannerA)->get(route('scanner.session-tickets', $otherSession))->assertForbidden();
    }

    // ---- Dashboard data authorization ----

    #[Test]
    public function org_dashboard_data_requires_org_role(): void
    {
        $org = $this->createOrg();
        $manager = $this->orgUser($org, RoleEnum::EventManager);
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($manager)->get(route('dashboard.data.org'))->assertOk()->assertJsonStructure([
            'revenue', 'overview', 'upcoming_events', 'org_id',
        ]);
        $this->actingAs($customer)->get(route('dashboard.data.org'))->assertForbidden();
    }

    #[Test]
    public function finance_dashboard_data_requires_finance_role(): void
    {
        $org = $this->createOrg();
        $finance = $this->orgUser($org, RoleEnum::FinanceManager);
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($finance)->get(route('dashboard.data.finance'))->assertOk()->assertJsonStructure(['revenue', 'recent_transactions']);
        $this->actingAs($customer)->get(route('dashboard.data.finance'))->assertForbidden();
    }

    #[Test]
    public function platform_dashboard_data_requires_platform_role(): void
    {
        $admin = $this->platformUser(RoleEnum::PlatformAdmin);
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($admin)->get(route('dashboard.data.platform'))->assertOk()->assertJsonStructure(['revenue', 'organizations']);
        $this->actingAs($customer)->get(route('dashboard.data.platform'))->assertForbidden();
    }

    #[Test]
    public function super_admin_dashboard_data_requires_super_admin(): void
    {
        $superAdmin = $this->platformUser(RoleEnum::SuperAdministrator);
        $admin = $this->platformUser(RoleEnum::PlatformAdmin);

        $this->actingAs($superAdmin)->get(route('dashboard.data.super-admin'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.data.super-admin'))->assertForbidden();
    }

    // ---- Organization reports ----

    #[Test]
    public function org_reports_accessible_to_org_roles_only(): void
    {
        $org = $this->createOrg();
        $manager = $this->orgUser($org, RoleEnum::EventManager);
        $finance = $this->orgUser($org, RoleEnum::FinanceManager);
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($manager)->get(route('org.reports'))->assertOk()->assertInertia(fn ($page) => $page->component('Org/Reports'));
        $this->actingAs($finance)->get(route('org.reports'))->assertOk();
        $this->actingAs($customer)->get(route('org.reports'))->assertForbidden();
    }

    // ---- Organization switching ----

    #[Test]
    public function member_can_switch_organizations(): void
    {
        $org = $this->createOrg();
        $manager = $this->orgUser($org, RoleEnum::EventManager);

        $this->actingAs($manager)
            ->from(route('dashboard'))
            ->put(route('organizations.switch', $org))
            ->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function non_member_cannot_switch_organizations(): void
    {
        $org = $this->createOrg();
        $customer = $this->platformUser(RoleEnum::Customer);

        $this->actingAs($customer)
            ->put(route('organizations.switch', $org))
            ->assertForbidden();
    }

    #[Test]
    public function platform_admin_can_switch_to_any_organization(): void
    {
        $org = $this->createOrg();
        $admin = $this->platformUser(RoleEnum::PlatformAdmin);

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->put(route('organizations.switch', $org))
            ->assertRedirect(route('dashboard'));
    }
}
