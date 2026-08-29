<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Controllers;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Organizations\Models\StaffInvitation;
use App\Domain\Organizations\Requests\InviteStaffRequest;
use App\Domain\Organizations\Requests\StoreOrganizationRequest;
use App\Domain\Organizations\Requests\UpdateOrganizationRequest;
use App\Domain\Organizations\Requests\UpdateStaffRoleRequest;
use App\Domain\Organizations\Services\OrganizationService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrganizationController
{
    public function __construct(
        private readonly OrganizationService $service,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        $organizations = $this->service->getActiveOrganizations();

        if ($request->expectsJson()) {
            return response()->json($organizations);
        }

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = $this->service->create($request->validated());

        return redirect()->route('organizations.settings', $organization)
            ->with('success', 'Organization created successfully.');
    }

    public function settings(Request $request, Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $staff = $this->service->getUsers($organization);
        $invitations = $this->service->getInvitations($organization);
        $currentUserRole = $this->service->getUserRoleInOrganization($organization, $request->user());

        $canManageStaff = $request->user()->can('manageStaff', $organization);

        $staffData = $staff->map(function (User $user) use ($organization) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $this->service->getUserRoleInOrganization($organization, $user),
            ];
        });

        return Inertia::render('Organizations/Settings', [
            'organization' => $organization->load('media'),
            'staff' => $staffData->values(),
            'invitations' => $invitations,
            'currentUserRole' => $currentUserRole,
            'canManageStaff' => $canManageStaff,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $organization->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        $this->service->update($organization, $data);

        return redirect()->back()->with('success', 'Organization updated successfully.');
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $this->service->delete($organization);

        return redirect()->route('organizations.index')
            ->with('success', 'Organization deleted.');
    }

    public function inviteStaff(InviteStaffRequest $request, Organization $organization): RedirectResponse
    {
        if (! $request->user()->can('manageStaff', $organization)) {
            throw new AccessDeniedHttpException('You do not have permission to invite staff.');
        }

        $invitation = $this->service->createInvitation(
            $organization,
            $request->input('email'),
            $request->input('role'),
            $request->user(),
        );

        $acceptUrl = route('organizations.invitations.accept', [
            'token' => $invitation->token,
        ]);

        return redirect()->back()->with('success', 'Invitation sent successfully.');
    }

    public function acceptInvitation(Request $request, string $token): RedirectResponse
    {
        $organization = $this->service->acceptInvitation($token, $request->user());

        if ($organization === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid or expired invitation.');
        }

        $request->user()->switchOrganization($organization);

        return redirect()->route('organizations.settings', $organization)
            ->with('success', 'You have joined the organization.');
    }

    public function cancelInvitation(Request $request, StaffInvitation $invitation): RedirectResponse
    {
        $organization = $invitation->organization;

        if (! $request->user()->can('manageStaff', $organization)) {
            throw new AccessDeniedHttpException('You do not have permission to cancel invitations.');
        }

        $this->service->cancelInvitation($invitation);

        return redirect()->back()->with('success', 'Invitation cancelled.');
    }

    public function updateStaffRole(UpdateStaffRoleRequest $request, Organization $organization, User $staff): RedirectResponse
    {
        if (! $request->user()->can('manageStaff', $organization)) {
            throw new AccessDeniedHttpException('You do not have permission to manage staff roles.');
        }

        $this->service->changeUserRole(
            $organization,
            $staff,
            $request->input('role'),
        );

        return redirect()->back()->with('success', 'Staff role updated.');
    }

    public function removeStaff(Request $request, Organization $organization, User $staff): RedirectResponse
    {
        if (! $request->user()->can('manageStaff', $organization)) {
            throw new AccessDeniedHttpException('You do not have permission to remove staff.');
        }

        $this->service->removeUser($organization, $staff);

        return redirect()->back()->with('success', 'Staff member removed.');
    }
}
