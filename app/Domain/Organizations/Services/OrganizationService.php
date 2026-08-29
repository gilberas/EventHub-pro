<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Services;

use App\Domain\Organizations\Events\OrganizationCreated;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Models\StaffInvitation;
use App\Domain\Organizations\Repositories\OrganizationRepository;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class OrganizationService
{
    public function __construct(
        private readonly OrganizationRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        $organization = $this->repository->create($data);

        OrganizationCreated::dispatch($organization);

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): ?Organization
    {
        return $this->repository->update($organization, $data);
    }

    public function delete(Organization $organization): bool
    {
        return $this->repository->delete($organization);
    }

    public function addUser(Organization $organization, User $user, string $role): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $user->assignRole($role);
    }

    public function removeUser(Organization $organization, User $user): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $roles = $user->getRoleNames()->toArray();

        foreach ($roles as $roleName) {
            $user->removeRole($roleName);
        }
    }

    public function changeUserRole(Organization $organization, User $user, string $role): void
    {
        $this->removeUser($organization, $user);
        $this->addUser($organization, $user, $role);
    }

    /** @return Collection<int, User> */
    public function getUsers(Organization $organization): Collection
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        return User::all()->filter(
            fn (User $user) => $user->roles()->exists()
        );
    }

    public function getUserRoleInOrganization(Organization $organization, User $user): ?string
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        return $user->getRoleNames()->first();
    }

    /** @return Collection<int, Organization> */
    public function getActiveOrganizations(): Collection
    {
        return $this->repository->all()->where('is_active', true);
    }

    public function createInvitation(Organization $organization, string $email, string $role, User $invitedBy): StaffInvitation
    {
        return StaffInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'invited_by_user_id' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /** @return Collection<int, StaffInvitation> */
    public function getInvitations(Organization $organization): Collection
    {
        return $organization->staffInvitations()->latest()->get();
    }

    public function acceptInvitation(string $token, User $user): ?Organization
    {
        $invitation = StaffInvitation::where('token', $token)->first();

        if ($invitation === null || $invitation->isExpired() || $invitation->isAccepted()) {
            return null;
        }

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            return null;
        }

        return DB::transaction(function () use ($invitation, $user) {
            $org = $invitation->organization;

            if (! $org->hasUser($user)) {
                $org->users()->attach($user->id);
            }

            $this->addUser($org, $user, $invitation->role);

            $invitation->markAsAccepted();

            return $org;
        });
    }

    public function cancelInvitation(StaffInvitation $invitation): void
    {
        $invitation->delete();
    }
}
