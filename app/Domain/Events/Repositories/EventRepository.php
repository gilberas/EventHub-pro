<?php

declare(strict_types=1);

namespace App\Domain\Events\Repositories;

use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EventRepository
{
    public function findById(int $id): ?Event
    {
        return Event::with(['sessions', 'organization'])->find($id);
    }

    public function findBySlug(string $slug): ?Event
    {
        return Event::with(['sessions', 'organization'])->where('slug', $slug)->first();
    }

    /** @return Collection<int, Event> */
    public function getPublished(): Collection
    {
        return Event::with(['sessions', 'organization'])
            ->where('status', 'published')
            ->whereHas('sessions', fn ($q) => $q->where('start_date', '>=', now()))
            ->latest()
            ->get();
    }

    /** @return Collection<int, Event> */
    public function getFeatured(): Collection
    {
        return Event::with(['sessions', 'organization'])
            ->where('status', 'published')
            ->where('is_featured', true)
            ->whereHas('sessions', fn ($q) => $q->where('start_date', '>=', now()))
            ->latest()
            ->take(6)
            ->get();
    }

    /** @return Collection<int, Event> */
    public function getTrending(): Collection
    {
        // Currently returns published events sorted by trending_score (manual flag).
        // FUTURE: Replace or augment with algorithmic trending based on Phase 11 analytics
        // (e.g., views, bookings, social shares, velocity of ticket sales).
        return Event::with(['sessions', 'organization'])
            ->where('status', 'published')
            ->whereNotNull('trending_score')
            ->orderByDesc('trending_score')
            ->take(8)
            ->get();
    }

    /** @return Collection<int, Event> */
    public function getByOrganization(Organization $organization): Collection
    {
        return Event::with(['sessions', 'organization'])
            ->where('organization_id', $organization->id)
            ->latest()
            ->get();
    }

    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return Event::with(['sessions', 'organization'])
            ->where('status', 'published')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        $query = Event::with(['sessions', 'organization'])
            ->where('status', 'published');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereHas('sessions', fn ($q) => $q->where('start_date', '>=', $filters['date_from']));
        }

        if (! empty($filters['date_to'])) {
            $query->whereHas('sessions', fn ($q) => $q->where('start_date', '<=', $filters['date_to']));
        }

        if (! empty($filters['location'])) {
            $location = $filters['location'];
            $query->whereHas('sessions', fn ($q) => $q->where('location', 'like', "%{$location}%"));
        }

        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $query->whereHas('sessions.ticketTypes', fn ($q) => $q->where('price', '>=', (float) $filters['price_min']));
        }

        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $query->whereHas('sessions.ticketTypes', fn ($q) => $q->where('price', '<=', (float) $filters['price_max']));
        }

        match ($filters['sort'] ?? null) {
            'date' => $query->withMin('sessions as earliest_session', 'start_date')
                ->orderBy('earliest_session'),
            'price' => $query->orderBy(
                TicketType::selectRaw('MIN(price)')
                    ->join('event_sessions', 'event_sessions.id', '=', 'ticket_types.event_session_id')
                    ->whereColumn('event_sessions.event_id', 'events.id'),
            ),
            'popularity' => $query->orderByRaw('trending_score IS NULL, trending_score DESC'),
            default => $query->latest(),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Event
    {
        return Event::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data): ?Event
    {
        $event->update($data);

        return $event->fresh();
    }

    public function delete(Event $event): bool
    {
        return (bool) $event->delete();
    }
}
