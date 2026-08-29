<?php

declare(strict_types=1);

namespace App\Domain\Bookings\Controllers;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingSeat;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function selectSeats(Event $event, EventSession $session): Response
    {
        $session->load(['event.organization', 'ticketTypes']);

        $hall = null;
        $seatStatus = [];

        $venue = $session->venue
            ?? $session->event?->venue
            ?? $session->event?->organization?->venues()->first();

        foreach ($venue?->halls ?? [] as $h) {
            $hall = $h->load('sections.rows.seats');
            break;
        }

        if ($hall) {
            $booked = BookingSeat::where('event_session_id', $session->id)
                ->pluck('seat_id')
                ->toArray();

            $held = SeatHold::where('event_session_id', $session->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->pluck('seat_id')
                ->toArray();

            $seatStatus = [
                'booked' => $booked,
                'held' => $held,
            ];
        }

        return Inertia::render('Events/SessionBooking', [
            'event' => $event->load('organization'),
            'session' => $session,
            'hall' => $hall,
            'seatStatus' => $seatStatus,
            'ticketTypes' => $session->ticketTypes,
        ]);
    }

    public function holdSeats(Request $request, EventSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'seat_ids' => 'required_without:quantity|array',
            'seat_ids.*' => 'exists:seats,id',
            'quantity' => 'required_without:seat_ids|integer|min:1|max:50',
        ]);

        /** @var User $user */
        $user = $request->user();
        $ticketType = TicketType::findOrFail($validated['ticket_type_id']);

        if (! empty($validated['seat_ids'])
            && count($validated['seat_ids']) > ($ticketType->max_per_order ?? 50)) {
            return redirect()->back()->withErrors([
                'hold' => "You may reserve at most {$ticketType->max_per_order} tickets per order.",
            ]);
        }

        if (! empty($validated['quantity'])) {
            if ($ticketType->max_per_order !== null && (int) $validated['quantity'] > $ticketType->max_per_order) {
                return redirect()->back()->withErrors([
                    'hold' => "You may purchase at most {$ticketType->max_per_order} tickets per order.",
                ]);
            }

            $available = $ticketType->quantity_available;
            if ($available !== null && (int) $validated['quantity'] > $available) {
                return redirect()->back()->withErrors([
                    'hold' => 'Not enough tickets available.',
                ]);
            }
        }

        try {
            if (! empty($validated['seat_ids'])) {
                $this->bookingService->holdSeats($session, $ticketType, $user, $validated['seat_ids']);
            } else {
                $this->bookingService->holdGATickets($session, $ticketType, $user, (int) $validated['quantity']);
            }
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['hold' => $e->getMessage()]);
        }

        return redirect()->route('checkout.review');
    }

    public function review(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $holds = SeatHold::with(['ticketType', 'seat.row.section'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->get();

        $subtotal = (float) $holds->sum(fn (SeatHold $hold) => (float) $hold->ticketType->price * $hold->quantity);
        $fees = round($subtotal * 0.05, 2);
        $total = round($subtotal + $fees, 2);

        return Inertia::render('Checkout/Review', [
            'holds' => $holds,
            'expiresAt' => $holds->isNotEmpty() ? $holds->min('expires_at')->toIso8601String() : null,
            'summary' => [
                'subtotal' => $subtotal,
                'fees' => $fees,
                'total' => $total,
            ],
            'gateways' => ['mock', 'stripe', 'paypal', 'mobile_money'],
            'defaultGateway' => config('services.payment.gateway', 'mock'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $holdIds = SeatHold::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->pluck('id')
            ->toArray();

        if (empty($holdIds)) {
            return redirect()->back()->withErrors(['checkout' => 'No active holds found.']);
        }

        try {
            $booking = $this->bookingService->checkout($user, $holdIds);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('bookings.show', $booking->reference);
    }

    public function showBooking(Request $request, string $reference): Response
    {
        $booking = Booking::with(['items.ticketType', 'seats.seat.row.section', 'eventSession.event.organization', 'tickets.seat.row.section'])
            ->where('reference', $reference)
            ->firstOrFail();

        /** @var User $user */
        $user = $request->user();

        if ($booking->user_id !== $user->id && ! $user->hasRole('PlatformAdmin')) {
            abort(403);
        }

        return Inertia::render('Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function myBookings(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $bookings = Booking::with(['eventSession.event.organization', 'items.ticketType'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }
}
