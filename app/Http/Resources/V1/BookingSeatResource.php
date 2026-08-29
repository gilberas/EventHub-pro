<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Bookings\Models\BookingSeat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookingSeat */
class BookingSeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seat_id' => $this->seat_id,
            'ticket_type_id' => $this->ticket_type_id,
        ];
    }
}
