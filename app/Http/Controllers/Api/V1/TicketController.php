<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Models\EventSession;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Services\TicketService;
use App\Http\Resources\V1\TicketResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tickets = Ticket::with(['eventSession.event', 'bookingItem.ticketType', 'seat'])
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => TicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function validateScan(Request $request): JsonResponse
    {
        $request->validate([
            'payload' => 'required|string',
            'event_session_id' => 'required|exists:event_sessions,id',
        ]);

        $session = EventSession::findOrFail($request->input('event_session_id'));
        $result = $this->ticketService->validateScan(
            rawPayload: $request->input('payload'),
            session: $session,
            scanner: $request->user(),
        );

        return response()->json($result);
    }
}
