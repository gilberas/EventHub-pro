<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Policies;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class OrganizationPolicy
{
    public function before(User $user): ?bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        if ($user->hasRole('SuperAdministrator')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $user->hasRole('PlatformAdmin');
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($organization->hasUser($user)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
            $user->unsetRelation('roles');

            if ($user->hasAnyRole([
                'OrganizationOwner', 'OrganizationAdmin', 'EventManager',
                'FinanceManager', 'SupportAgent', 'TicketScanner',
            ])) {
                return true;
            }
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $user->unsetRelation('roles');

        return $user->hasRole('PlatformAdmin');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $user->unsetRelation('roles');

        if ($user->hasRole(['OrganizationOwner', 'OrganizationAdmin'])) {
            return true;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $user->unsetRelation('roles');

        return $user->hasRole('PlatformAdmin');
    }

    public function delete(User $user, Organization $organization): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $user->unsetRelation('roles');

        return $user->hasRole('OrganizationOwner');
    }

    public function manageStaff(User $user, Organization $organization): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $user->unsetRelation('roles');

        return $user->hasRole(['OrganizationOwner', 'OrganizationAdmin']);
    }
}
