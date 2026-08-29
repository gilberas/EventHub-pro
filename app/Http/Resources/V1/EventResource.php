<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Events\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'tags' => $this->tags,
            'status' => $this->status,
            'age_restriction' => $this->age_restriction,
            'featured' => $this->featured,
            'trending' => $this->trending,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'sessions' => EventSessionResource::collection($this->whenLoaded('sessions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
