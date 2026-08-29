<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Controllers;

use App\Domain\Events\Models\EventSession;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Requests\ScanTicketRequest;
use App\Domain\Tickets\Services\TicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TicketScannerController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function scanner(): Response
    {
        return Inertia::render('Tickets/Scanner');
    }

    public function scan(ScanTicketRequest $request): JsonResponse
    {
        $session = EventSession::findOrFail($request->input('event_session_id'));
        $this->assertSessionInScope($request, $session);

        $result = $this->ticketService->validateScan(
            rawPayload: $request->input('payload'),
            session: $session,
            scanner: $request->user(),
        );

        return response()->json($result);
    }

    public function manualCheckIn(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_number' => 'required|string|max:32|exists:tickets,ticket_number',
        ]);

        $ticket = Ticket::where('ticket_number', $request->input('ticket_number'))->firstOrFail();

        $session = $ticket->eventSession;

        if ($session !== null) {
            $this->assertSessionInScope($request, $session);
        }

        $result = $this->ticketService->manualCheckIn($ticket, $request->user());

        return response()->json($result);
    }

    public function sessionTickets(Request $request, EventSession $session): JsonResponse
    {
        $this->assertSessionInScope($request, $session);

        $tickets = Ticket::where('event_session_id', $session->id)
            ->with(['booking.user', 'bookingItem.ticketType', 'seat.section.row', 'checkedInBy'])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'session' => $session->load('event'),
            'tickets' => $tickets,
            'stats' => [
                'total' => $tickets->count(),
                'checked_in' => $tickets->where('checked_in_at', '!=', null)->count(),
                'active' => $tickets->where('checked_in_at', null)->count(),
            ],
        ]);
    }

    /**
     * Platform roles may scan any session; org-scoped roles are limited to their own organization.
     */
    private function assertSessionInScope(Request $request, EventSession $session): void
    {
        $user = $request->user();

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $user->unsetRelation('roles');

        if ($user->hasAnyRole(['PlatformAdmin', 'SuperAdministrator'])) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->currentOrganizationId());
        $user->unsetRelation('roles');

        if ($user->hasAnyRole(['TicketScanner', 'OrganizationAdmin', 'OrganizationOwner'])) {
            $orgId = $user->currentOrganizationId();

            if ($orgId !== null && (int) $session->event->organization_id === $orgId) {
                return;
            }
        }

        throw new AccessDeniedHttpException('You are not authorized to scan tickets for this session.');
    }
}
