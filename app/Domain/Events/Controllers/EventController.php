<?php

declare(strict_types=1);

namespace App\Domain\Events\Controllers;

use App\Domain\Events\DTOs\EventDTO;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Requests\StoreEventRequest;
use App\Domain\Events\Requests\UpdateEventRequest;
use App\Domain\Events\Services\EventService;
use App\Domain\Venues\Models\Venue;
use App\Shared\Enums\EventStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController
{
    public function __construct(
        private readonly EventService $service,
    ) {}

    public function index(Request $request): Response
    {
        $featured = $this->service->getFeaturedEvents()->map(fn (Event $e) => EventDTO::fromModel($e)->toArray());
        $trending = $this->service->getTrendingEvents()->map(fn (Event $e) => EventDTO::fromModel($e)->toArray());

        return Inertia::render('Welcome', [
            'featuredEvents' => $featured->values(),
            'trendingEvents' => $trending->values(),
        ]);
    }

    public function search(Request $request): Response
    {
        $filters = $request->only(['category', 'date_from', 'date_to', 'location', 'price_min', 'price_max', 'q', 'sort']);
        $results = $this->service->searchEvents($filters);

        return Inertia::render('Events/Search', [
            'events' => $results->getCollection()->map(fn (Event $e) => EventDTO::fromModel($e)->toArray())->values(),
            'filters' => $filters,
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $event = Event::with(['sessions.ticketTypes', 'organization'])->where('slug', $slug)->firstOrFail();

        if (! $event->isPublished()) {
            abort(404);
        }

        return Inertia::render('Events/Show', [
            'event' => EventDTO::fromModel($event)->toArray(),
            'is_favorited' => $request->user() !== null ? $event->isFavoritedBy($request->user()) : false,
        ]);
    }

    public function indexForOrg(Request $request): Response
    {
        $orgId = $request->user()->currentOrganizationId();

        $events = Event::with(['sessions', 'organization', 'media'])
            ->where('organization_id', $orgId)
            ->latest()
            ->get()
            ->map(fn (Event $e) => EventDTO::fromModel($e)->toArray());

        return Inertia::render('Org/Events/Index', [
            'events' => $events->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Event::class);

        $venues = Venue::where('organization_id', $request->user()->currentOrganizationId())
            ->with('halls')
            ->get();

        return Inertia::render('Org/Events/Create', [
            'venues' => $venues,
        ]);
    }

    public function edit(Request $request, Event $event): Response
    {
        Gate::authorize('update', $event);

        $event->load(['sessions.ticketTypes', 'media']);
        $venues = Venue::where('organization_id', $request->user()->currentOrganizationId())
            ->with('halls')
            ->get();

        return Inertia::render('Org/Events/Edit', [
            'event' => EventDTO::fromModel($event)->toArray(),
            'venues' => $venues,
        ]);
    }

    public function toggleStatus(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $status = match ($request->string('status')->toString()) {
            'published' => EventStatus::Published,
            'cancelled' => EventStatus::Cancelled,
            'completed' => EventStatus::Completed,
            default => EventStatus::Draft,
        };

        $event->update(['status' => $status->value]);

        return redirect()->route('org.events.index')
            ->with('success', "Event {$event->title} marked as {$status->label()}.");
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $event = $this->service->create(array_merge(
            $request->validated(),
            ['organization_id' => $request->user()->currentOrganizationId()],
        ));

        if ($request->hasFile('cover')) {
            $event->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $event->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect()->route('events.show', $event->slug)
            ->with('success', 'Event created successfully.');
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $this->service->update($event, $request->validated());

        if ($request->hasFile('cover')) {
            $event->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $event->addMedia($image)->toMediaCollection('gallery');
            }
        }

        return redirect()->back()->with('success', 'Event updated.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $this->service->delete($event);

        return redirect()->route('org.events.index')
            ->with('success', 'Event deleted.');
    }

    public function joinWaitingList(Request $request, Event $event): RedirectResponse
    {
        $this->service->joinWaitingList($event, $request->user());

        return redirect()->back()->with('success', 'You have joined the waiting list.');
    }

    public function leaveWaitingList(Request $request, Event $event): RedirectResponse
    {
        $this->service->leaveWaitingList($event, $request->user());

        return redirect()->back()->with('success', 'You have left the waiting list.');
    }
}
