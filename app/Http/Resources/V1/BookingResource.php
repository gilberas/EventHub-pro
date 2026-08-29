<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Bookings\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'fees' => (float) $this->fees,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'event_session' => new EventSessionResource($this->whenLoaded('eventSession')),
            'items' => BookingItemResource::collection($this->whenLoaded('items')),
            'seats' => BookingSeatResource::collection($this->whenLoaded('seats')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
