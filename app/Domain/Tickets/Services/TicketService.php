<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Services;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Events\Models\EventSession;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketScanLog;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TicketService
{
    private const HMAC_ALGO = 'sha256';

    public function generateTickets(Booking $booking): array
    {
        $booking->loadMissing('items.ticketType', 'seats.seat', 'eventSession');

        $tickets = [];

        foreach ($booking->items as $item) {
            $quantity = (int) $item->quantity;
            $seatAssignments = $booking->seats
                ->where('ticket_type_id', $item->ticket_type_id)
                ->values();

            for ($i = 0; $i < $quantity; $i++) {
                $seat = $seatAssignments->get($i);

                $ticket = $this->createTicket(
                    booking: $booking,
                    bookingItem: $item,
                    seatId: $seat?->seat_id,
                );

                $tickets[] = $ticket;
            }
        }

        return $tickets;
    }

    public function createTicket(Booking $booking, $bookingItem, ?int $seatId = null): Ticket
    {
        $ticketNumber = Ticket::generateTicketNumber();
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'booking_item_id' => $bookingItem->id,
            'event_session_id' => $booking->event_session_id,
            'ticket_type_id' => $bookingItem->ticket_type_id,
            'seat_id' => $seatId,
            'ticket_number' => $ticketNumber,
            'qr_payload' => '',
            'status' => 'active',
        ]);

        $qrPayload = $this->generateQrPayload($ticket, $booking->eventSession);
        $ticket->update(['qr_payload' => $qrPayload]);

        return $ticket->fresh();
    }

    public function generateQrPayload(Ticket $ticket, EventSession $session): string
    {
        $data = json_encode([
            'ticket_id' => $ticket->id,
            'session_id' => $session->id,
            'nonce' => bin2hex(random_bytes(8)),
        ]);

        $encrypted = Crypt::encryptString($data);
        $signature = $this->sign($encrypted);

        return base64_encode($encrypted.'.'.$signature);
    }

    public function validateScan(string $rawPayload, EventSession $session, User $scanner): array
    {
        $decoded = base64_decode($rawPayload, true);

        if ($decoded === false || $decoded === '') {
            $this->logScan(null, $scanner, $session->id, 'invalid_payload', $rawPayload, 'Failed to base64 decode');

            return ['valid' => false, 'error' => 'Invalid QR code format.'];
        }

        $parts = explode('.', $decoded, 2);

        if (count($parts) !== 2) {
            $this->logScan(null, $scanner, $session->id, 'invalid_payload', $rawPayload, 'Missing signature separator');

            return ['valid' => false, 'error' => 'Invalid QR code format.'];
        }

        [$encrypted, $signature] = $parts;

        if (! $this->verifySignature($encrypted, $signature)) {
            $this->logScan(null, $scanner, $session->id, 'invalid_payload', $rawPayload, 'HMAC signature mismatch');

            return ['valid' => false, 'error' => 'QR code has been tampered with.'];
        }

        try {
            $decrypted = Crypt::decryptString($encrypted);
            $payload = json_decode($decrypted, true, 2);
        } catch (\Throwable $e) {
            $this->logScan(null, $scanner, $session->id, 'invalid_payload', $rawPayload, 'Decryption failed: '.$e->getMessage());

            return ['valid' => false, 'error' => 'Invalid QR code payload.'];
        }

        if (! isset($payload['ticket_id']) || ! isset($payload['session_id'])) {
            $this->logScan(null, $scanner, $session->id, 'invalid_payload', $rawPayload, 'Missing required payload fields');

            return ['valid' => false, 'error' => 'Invalid QR code payload.'];
        }

        return DB::transaction(function () use ($payload, $session, $scanner, $rawPayload) {
            $ticketId = (int) $payload['ticket_id'];
            $sessionId = (int) $payload['session_id'];

            if ($sessionId !== $session->id) {
                $this->logScan(null, $scanner, $session->id, 'wrong_event', $rawPayload, "Ticket session {$sessionId} does not match scanned session {$session->id}");

                return ['valid' => false, 'error' => 'This ticket is for a different event session.'];
            }

            $ticket = Ticket::lockForUpdate()->find($ticketId);

            if (! $ticket) {
                $this->logScan(null, $scanner, $session->id, 'ticket_not_found', $rawPayload, "Ticket ID {$ticketId} not found");

                return ['valid' => false, 'error' => 'Ticket not found.'];
            }

            if ($ticket->event_session_id !== $session->id) {
                $this->logScan($ticket, $scanner, $session->id, 'wrong_event', $rawPayload, "Ticket session {$ticket->event_session_id} does not match scanned session {$session->id}");

                return ['valid' => false, 'error' => 'This ticket is for a different event session.'];
            }

            if ($ticket->status === 'refunded' || $ticket->status === 'void') {
                $this->logScan($ticket, $scanner, $session->id, 'invalid_payload', $rawPayload, "Ticket status is {$ticket->status}");

                return ['valid' => false, 'error' => "This ticket has been {$ticket->status}."];
            }

            if ($ticket->isCheckedIn()) {
                $this->logScan($ticket, $scanner, $session->id, 'already_used', $rawPayload);

                return [
                    'valid' => false,
                    'error' => 'This ticket has already been used.',
                    'checked_in_at' => $ticket->checked_in_at,
                ];
            }

            $ticket->update([
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $scanner->id,
                'status' => 'used',
            ]);

            $this->logScan($ticket, $scanner, $session->id, 'valid', $rawPayload);

            return [
                'valid' => true,
                'ticket' => $ticket->fresh()->load('booking.user', 'bookingItem.ticketType', 'seat'),
            ];
        });
    }

    public function manualCheckIn(Ticket $ticket, User $scanner): array
    {
        return DB::transaction(function () use ($ticket, $scanner) {
            $locked = Ticket::lockForUpdate()->find($ticket->id);

            if (! $locked) {
                return ['valid' => false, 'error' => 'Ticket not found.'];
            }

            if ($locked->isCheckedIn()) {
                return ['valid' => false, 'error' => 'Ticket already used.', 'checked_in_at' => $locked->checked_in_at];
            }

            $locked->update([
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $scanner->id,
                'status' => 'used',
            ]);

            $this->logScan($locked, $scanner, $locked->event_session_id, 'valid', null, 'Manual entry');

            return ['valid' => true, 'ticket' => $locked->fresh()->load('booking.user', 'bookingItem.ticketType', 'seat')];
        });
    }

    private function sign(string $data): string
    {
        $secret = (string) config('app.key');

        return hash_hmac(self::HMAC_ALGO, $data, $secret);
    }

    private function verifySignature(string $data, string $signature): bool
    {
        $expected = $this->sign($data);

        return hash_equals($expected, $signature);
    }

    private function logScan(?Ticket $ticket, User $scanner, int $sessionId, string $result, ?string $rawPayload = null, ?string $errorMessage = null): TicketScanLog
    {
        return TicketScanLog::create([
            'ticket_id' => $ticket?->id,
            'scanned_by_user_id' => $scanner->id,
            'event_session_id' => $sessionId,
            'result' => $result,
            'raw_payload' => $rawPayload,
            'error_message' => $errorMessage,
        ]);
    }
}
