<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Services\EventService;
use App\Http\Resources\V1\EventResource;
use App\Shared\Enums\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $events = Event::with(['sessions.ticketTypes', 'organization'])
            ->where('status', EventStatus::Published)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->input('q').'%'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => EventResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $event = Event::with(['sessions.ticketTypes', 'organization'])
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $event->isPublished()) {
            abort(404);
        }

        return response()->json([
            'data' => new EventResource($event),
        ]);
    }

    public function featured(): JsonResponse
    {
        $events = $this->eventService->getFeaturedEvents();

        return response()->json([
            'data' => EventResource::collection($events),
        ]);
    }
}
