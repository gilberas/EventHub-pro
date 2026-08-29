<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Listeners;

use App\Domain\Payments\Events\BookingInvoiceGenerated;
use App\Domain\Tickets\Jobs\SendTicketEmail;
use App\Domain\Tickets\Services\TicketService;
use Illuminate\Support\Facades\Log;

class GenerateTicketsListener
{
    public function __construct(
        private readonly TicketService $ticketService,
    ) {}

    public function handle(BookingInvoiceGenerated $event): void
    {
        try {
            $tickets = $this->ticketService->generateTickets($event->booking);

            SendTicketEmail::dispatch($event->booking, $tickets);
        } catch (\Throwable $e) {
            Log::error('Failed to generate tickets for booking '.$event->booking->reference, [
                'error' => $e->getMessage(),
                'booking_id' => $event->booking->id,
            ]);
        }
    }
}
