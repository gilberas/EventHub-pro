<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() === 0) {
            $this->command->warn('No users found, skipping OrganizationSeeder.');

            return;
        }

        $ownerRole = RoleEnum::OrganizationOwner->name;
        $adminRole = RoleEnum::OrganizationAdmin->name;
        $eventManagerRole = RoleEnum::EventManager->name;
        $financeRole = RoleEnum::FinanceManager->name;
        $supportRole = RoleEnum::SupportAgent->name;
        $scannerRole = RoleEnum::TicketScanner->name;

        $org = Organization::create([
            'name' => 'Acme Events Inc.',
            'slug' => 'acme-events',
            'domain' => null,
            'subscription_plan' => 'enterprise',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'billing_email' => 'billing@acme-events.test',
            'billing_address' => '123 Broadway, New York, NY 10001',
            'refund_policy_days' => 14,
            'refund_policy_percentage' => 100.00,
            'is_active' => true,
        ]);

        $user = User::first();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $org->users()->attach($user->id);
        $user->assignRole($ownerRole);

        if (User::count() >= 2) {
            $admin = User::skip(1)->first();
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $org->users()->attach($admin->id);
            $admin->assignRole($adminRole);
        }

        if (User::count() >= 3) {
            $eventMgr = User::skip(2)->first();
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $org->users()->attach($eventMgr->id);
            $eventMgr->assignRole($eventManagerRole);
        }

        if (User::count() >= 4) {
            $finance = User::skip(3)->first();
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $org->users()->attach($finance->id);
            $finance->assignRole($financeRole);
        }

        if (User::count() >= 5) {
            $support = User::skip(4)->first();
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $org->users()->attach($support->id);
            $support->assignRole($supportRole);
        }

        if (User::count() >= 6) {
            $scanner = User::skip(5)->first();
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $org->users()->attach($scanner->id);
            $scanner->assignRole($scannerRole);
        }

        $this->command->info("Seeded organization '{$org->name}' with {$org->users()->count()} staff members.");
    }
}
