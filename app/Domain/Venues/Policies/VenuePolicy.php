<?php

declare(strict_types=1);

namespace App\Domain\Venues\Policies;

use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\PermissionRegistrar;

class VenuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Venue $venue): bool
    {
        if ($venue->is_active) {
            return true;
        }

        $currentOrgId = $user->currentOrganizationId();

        if ($currentOrgId === null || (int) $currentOrgId !== $venue->organization_id) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $currentOrgId);
        $user->unsetRelation('roles');

        return $user->hasAnyRole(['OrganizationOwner', 'OrganizationAdmin', 'EventManager']);
    }

    public function create(User $user): bool
    {
        $currentOrgId = $user->currentOrganizationId();

        if ($currentOrgId === null) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $currentOrgId);
        $user->unsetRelation('roles');

        return $user->hasAnyRole(['OrganizationOwner', 'OrganizationAdmin', 'EventManager']);
    }

    public function update(User $user, Venue $venue): bool
    {
        $currentOrgId = $user->currentOrganizationId();

        if ($currentOrgId === null || (int) $currentOrgId !== $venue->organization_id) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $currentOrgId);
        $user->unsetRelation('roles');

        return $user->hasAnyRole(['OrganizationOwner', 'OrganizationAdmin', 'EventManager']);
    }

    public function delete(User $user, Venue $venue): bool
    {
        $currentOrgId = $user->currentOrganizationId();

        if ($currentOrgId === null || (int) $currentOrgId !== $venue->organization_id) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $currentOrgId);
        $user->unsetRelation('roles');

        return $user->hasAnyRole(['OrganizationOwner', 'OrganizationAdmin']);
    }

    public function duplicate(User $user, Venue $venue): bool
    {
        return $this->update($user, $venue);
    }
}
