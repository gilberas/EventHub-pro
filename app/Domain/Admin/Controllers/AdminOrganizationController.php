<?php

declare(strict_types=1);

namespace App\Domain\Admin\Controllers;

use App\Domain\Admin\Services\AdminService;
use App\Domain\Organizations\Models\Organization;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrganizationController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    public function index(Request $request): Response
    {
        $orgs = $this->adminService->listOrganizations();

        return Inertia::render('Admin/Organizations', [
            'organizations' => $orgs,
        ]);
    }

    public function toggleStatus(Organization $organization): RedirectResponse
    {
        $org = $this->adminService->toggleOrganizationStatus($organization);

        return redirect()->back()->with('success', sprintf(
            'Organization "%s" is now %s.',
            $org->name,
            $org->is_active ? 'active' : 'suspended',
        ));
    }
}
