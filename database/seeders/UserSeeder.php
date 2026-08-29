<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Shared\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Seeded test credentials (keep in sync with seedUsers()):
 *
 * | # | Role                   | Email                                 | Password | Organization     |
 * |---|------------------------|---------------------------------------|----------|------------------|
 * | 1 | Organization Owner     | org-owner@test.eventhub.local         | password | Acme Events Inc. |
 * | 2 | Organization Admin     | org-admin@test.eventhub.local         | password | Acme Events Inc. |
 * | 3 | Event Manager          | event-manager@test.eventhub.local     | password | Acme Events Inc. |
 * | 4 | Finance Manager        | finance-manager@test.eventhub.local   | password | Acme Events Inc. |
 * | 5 | Support Agent          | support-agent@test.eventhub.local     | password | Acme Events Inc. |
 * | 6 | Ticket Scanner         | ticket-scanner@test.eventhub.local    | password | Acme Events Inc. |
 * | 7 | Customer               | customer@test.eventhub.local          | password | —                |
 * | 8 | Platform Admin         | platform-admin@test.eventhub.local    | password | —                |
 * | 9 | Super Administrator    | super-admin@test.eventhub.local       | password | —                |
 *
 * If you modify seedUsers(), update this table to match.
 */
class UserSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const ORGANIZATION_NAME = 'Acme Events Inc.';

    /**
     * @return array{name: string, email: string, role: RoleEnum, password: string, email_verified_at: Carbon}
     */
    private function userDefinition(string $name, string $slug, RoleEnum $role): array
    {
        return [
            'name' => $name,
            'email' => "{$slug}@test.eventhub.local",
            'role' => $role,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
        ];
    }

    /**
     * Users are ordered so OrganizationSeeder picks them up sequentially.
     *
     * Positions 0-5 are org-scoped users that OrganizationSeeder attaches to Acme Events.
     * Positions 6-8 are non-org users (Customer, PlatformAdmin, SuperAdministrator).
     */
    private function seedUsers(): array
    {
        return [
            // Org-scoped users (positions 0-5) — OrganizationSeeder handles org attachment & role
            $this->userDefinition('Org Owner', 'org-owner', RoleEnum::OrganizationOwner),
            $this->userDefinition('Org Admin', 'org-admin', RoleEnum::OrganizationAdmin),
            $this->userDefinition('Event Manager', 'event-manager', RoleEnum::EventManager),
            $this->userDefinition('Finance Manager', 'finance-manager', RoleEnum::FinanceManager),
            $this->userDefinition('Support Agent', 'support-agent', RoleEnum::SupportAgent),
            $this->userDefinition('Ticket Scanner', 'ticket-scanner', RoleEnum::TicketScanner),

            // Non-org users (positions 6+)
            $this->userDefinition('Customer', 'customer', RoleEnum::Customer),
            $this->userDefinition('Platform Admin', 'platform-admin', RoleEnum::PlatformAdmin),
            $this->userDefinition('Super Admin', 'super-admin', RoleEnum::SuperAdministrator),
        ];
    }

    /**
     * Build the credentials table from seedUsers() as the single source of truth.
     *
     * Returns a human-readable table string suitable for console output.
     */
    public static function credentialsTable(): string
    {
        $lines = [];
        $header = sprintf(' %-3s | %-22s | %-38s | %-8s | %-16s ', '#', 'Role', 'Email', 'Password', 'Organization');
        $separator = str_repeat('-', strlen($header));

        $lines[] = $header;
        $lines[] = $separator;

        $password = self::PASSWORD;

        foreach ((new self)->seedUsers() as $i => $def) {
            $num = $i + 1;
            $role = $def['role'];
            $org = ($role->isPlatformRole() || $role === RoleEnum::Customer) ? "\u{2014}" : self::ORGANIZATION_NAME;

            $lines[] = sprintf(' %-3s | %-22s | %-38s | %-8s | %-16s ',
                $num, $role->label(), $def['email'], $password, $org);
        }

        return implode(PHP_EOL, $lines);
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('UserSeeder must never run in production.');
        }

        $hashedPassword = Hash::make(self::PASSWORD);

        foreach ($this->seedUsers() as $def) {
            $user = User::create([
                'name' => $def['name'],
                'email' => $def['email'],
                'password' => $hashedPassword,
                'email_verified_at' => $def['email_verified_at'],
            ]);

            if ($def['role']->isPlatformRole() || $def['role'] === RoleEnum::Customer) {
                $user->assignRole($def['role']->name);
            }
        }

        $this->command->info('Seeded '.count($this->seedUsers()).' test users.');
        $this->command->newLine();
        $this->command->info('Test credentials:');
        $this->command->line(static::credentialsTable());
    }
}
