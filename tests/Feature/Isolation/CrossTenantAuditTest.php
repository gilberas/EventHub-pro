<?php

declare(strict_types=1);

use App\Domain\Cms\Models\BlogPost;
use App\Domain\Cms\Models\FaqItem;
use App\Domain\Cms\Models\Sponsor;
use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use App\Shared\Enums\EventStatus;
use App\Shared\Enums\RoleEnum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('prevents cross-tenant event access via policies', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::OrganizationOwner->name);

    $eventB = Event::factory()->create([
        'organization_id' => $orgB->id,
        'status' => EventStatus::Draft,
    ]);

    // Set team to Org A — user should NOT access Org B's event
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);

    expect(Gate::forUser($user)->allows('update', $eventB))->toBeFalse();
});

it('prevents cross-tenant venue access via policies', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::OrganizationOwner->name);

    $venueB = Venue::factory()->create(['organization_id' => $orgB->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);

    expect(Gate::forUser($user)->allows('update', $venueB))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $venueB))->toBeFalse();
});

it('prevents cross-tenant organization access via policies', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::OrganizationOwner->name);

    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);

    expect(Gate::forUser($user)->allows('update', $orgB))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $orgB))->toBeFalse();
});

it('allows super admin to access any resource', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleEnum::SuperAdministrator->name);

    $eventB = Event::factory()->create([
        'organization_id' => $orgB->id,
        'status' => EventStatus::Draft,
    ]);

    expect(Gate::forUser($superAdmin)->allows('update', $eventB))->toBeTrue();
    expect(Gate::forUser($superAdmin)->allows('update', $orgA))->toBeTrue();
    expect(Gate::forUser($superAdmin)->allows('delete', $orgB))->toBeTrue();
});

it('verifies platform-only models are not org-scoped', function () {
    $post = BlogPost::create(['title' => 'Test', 'slug' => 'test', 'content' => 'C', 'author_name' => 'A']);
    $faq = FaqItem::create(['question' => 'Q', 'answer' => 'A', 'sort_order' => 1]);
    $sponsor = Sponsor::create(['name' => 'S', 'sort_order' => 1]);

    expect(BlogPost::count())->toBe(1);
    expect(FaqItem::count())->toBe(1);
    expect(Sponsor::count())->toBe(1);
});
