<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Controllers;

use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class TicketController
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $tickets = Ticket::with([
            'eventSession.event.organization',
            'booking',
            'bookingItem.ticketType',
            'seat.row.section',
        ])
            ->whereHas('booking', fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Tickets/MyTickets', [
            'tickets' => $tickets,
        ]);
    }

    public function qr(Request $request, Ticket $ticket): HttpFoundationResponse
    {
        if ($ticket->booking->user_id !== $request->user()->id && ! $request->user()->hasRole('PlatformAdmin')) {
            abort(403);
        }

        $qr = new QRCode;

        return response($qr->render($ticket->qr_payload))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'private, max-age=300');
    }
}
