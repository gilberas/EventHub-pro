<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Events\Models\EventSession;
use App\Http\Resources\V1\BookingResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function holdSeats(Request $request, EventSession $session): JsonResponse
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

        try {
            if (! empty($validated['seat_ids'])) {
                $this->bookingService->holdSeats($session, $ticketType, $user, $validated['seat_ids']);
            } else {
                $this->bookingService->holdGATickets($session, $ticketType, $user, (int) $validated['quantity']);
            }
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['message' => 'Seats held successfully.'], 201);
    }

    public function checkout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $holdIds = SeatHold::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->pluck('id')
            ->toArray();

        if (empty($holdIds)) {
            return response()->json(['message' => 'No active holds found.'], 400);
        }

        try {
            $booking = $this->bookingService->checkout($user, $holdIds);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'data' => new BookingResource($booking->load(['items.ticketType', 'seats', 'eventSession.event'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bookings = Booking::with(['items.ticketType', 'eventSession.event.organization'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(Request $request, string $reference): JsonResponse
    {
        $booking = Booking::with(['items.ticketType', 'seats', 'eventSession.event.organization', 'tickets'])
            ->where('reference', $reference)
            ->firstOrFail();

        /** @var User $user */
        $user = $request->user();

        if ($booking->user_id !== $user->id) {
            abort(403);
        }

        return response()->json([
            'data' => new BookingResource($booking),
        ]);
    }
}
