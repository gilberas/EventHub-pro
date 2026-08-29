<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Tickets\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'status' => $this->status,
            'qr_payload' => $this->qr_payload,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'event_session' => new EventSessionResource($this->whenLoaded('eventSession')),
            'ticket_type' => new TicketTypeResource($this->whenLoaded('ticketType')),
            'seat' => $this->whenLoaded('seat'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
