<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $platformRoles = 0;
        $organizationRoles = 0;

        foreach (RoleEnum::cases() as $role) {
            $exists = Role::where('name', $role->name)
                ->where('guard_name', 'web')
                ->whereNull('organization_id')
                ->exists();

            if ($exists) {
                $this->command->warn("Role [{$role->name}] already exists, skipping.");

                continue;
            }

            Role::create([
                'name' => $role->name,
                'guard_name' => 'web',
                'organization_id' => null,
            ]);

            if ($role->isPlatformRole()) {
                $platformRoles++;
            } else {
                $organizationRoles++;
            }

            $this->command->info("Created role: {$role->name}");
        }

        $this->command->info("Seeded {$platformRoles} platform roles and {$organizationRoles} organization roles.");
    }
}
