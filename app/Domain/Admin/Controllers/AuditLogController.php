<?php

declare(strict_types=1);

namespace App\Domain\Admin\Controllers;

use App\Domain\Admin\Services\AdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    public function index(Request $request): Response
    {
        $logs = $this->adminService->getAuditLogs();

        return Inertia::render('Admin/AuditLog', [
            'logs' => $logs,
        ]);
    }
}
