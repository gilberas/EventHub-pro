<?php

declare(strict_types=1);

namespace App\Domain\Events\Policies;

use App\Domain\Events\Models\Event;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class EventPolicy
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
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        if ($event->isPublished()) {
            return true;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($event->organization_id);
        $user->unsetRelation('roles');

        return $user->hasAnyRole([
            'EventManager', 'OrganizationAdmin', 'OrganizationOwner',
            'PlatformAdmin', 'SuperAdministrator',
        ]);
    }

    public function create(User $user): bool
    {
        $orgId = $user->currentOrganizationId();

        if ($orgId === null) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);
        $user->unsetRelation('roles');

        return $user->hasAnyRole(['EventManager', 'OrganizationAdmin', 'OrganizationOwner']);
    }

    public function update(User $user, Event $event): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($event->organization_id);
        $user->unsetRelation('roles');

        if ($user->hasAnyRole(['EventManager', 'OrganizationAdmin', 'OrganizationOwner'])) {
            return true;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $user->hasRole('PlatformAdmin');
    }

    public function delete(User $user, Event $event): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($event->organization_id);
        $user->unsetRelation('roles');

        if ($user->hasAnyRole(['OrganizationAdmin', 'OrganizationOwner'])) {
            return true;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $user->hasRole('PlatformAdmin');
    }
}
