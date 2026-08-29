<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Bookings\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketType */
class TicketTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'currency' => $this->currency ?? 'USD',
            'quantity_available' => $this->quantity_available,
            'mode' => $this->mode,
            'sort_order' => $this->sort_order,
        ];
    }
}
