<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Venues\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Venue */
class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
