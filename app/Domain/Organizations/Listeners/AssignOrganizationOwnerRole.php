<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Listeners;

use App\Domain\Organizations\Events\OrganizationCreated;
use Spatie\Permission\PermissionRegistrar;

class AssignOrganizationOwnerRole
{
    public function handle(OrganizationCreated $event): void
    {
        $organization = $event->organization;

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $organization->users()->attach(auth()->id());

        auth()->user()->assignRole('OrganizationOwner');
    }
}
