<?php

declare(strict_types=1);

namespace App\Domain\Events\Services;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Events\Models\WaitingListEntry;
use App\Domain\Events\Repositories\EventRepository;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(
        private readonly EventRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Event
    {
        $data['slug'] = Event::generateSlug($data['title']);

        return DB::transaction(function () use ($data) {
            /** @var array{sessions?: array<int, array<string, mixed>>} $data */
            $sessionsData = $data['sessions'] ?? [];
            unset($data['sessions']);

            /** @var Event $event */
            $event = $this->repository->create($data);

            foreach ($sessionsData as $sessionData) {
                $this->createSession($event, $sessionData);
            }

            if (empty($sessionsData)) {
                $event->sessions()->create([
                    'start_date' => $data['start_date'] ?? now()->addMonth(),
                    'end_date' => $data['end_date'] ?? now()->addMonth()->addHours(3),
                ]);
            }

            return $event;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data): ?Event
    {
        if (isset($data['title']) && $data['title'] !== $event->title) {
            $data['slug'] ??= Event::generateSlug($data['title'], $event->id);
        }

        return $this->repository->update($event, $data);
    }

    public function delete(Event $event): bool
    {
        return $this->repository->delete($event);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSession(Event $event, array $data): EventSession
    {
        /** @var array<int, array<string, mixed>> $ticketTypeData */
        $ticketTypeData = $data['ticket_types'] ?? [];
        unset($data['ticket_types']);

        $session = $event->sessions()->create($data);

        foreach ($ticketTypeData as $ticketData) {
            $session->ticketTypes()->create([
                'name' => $ticketData['name'],
                'price' => $ticketData['price'],
                'description' => $ticketData['description'] ?? null,
                'mode' => $ticketData['mode'] ?? 'general_admission',
                'quantity_available' => $ticketData['quantity_available'] ?? null,
                'max_per_order' => $ticketData['max_per_order'] ?? 1,
                'sort_order' => $ticketData['sort_order'] ?? 1,
            ]);
        }

        if (! empty($data['recurrence_rule'])) {
            $this->generateRecurringSessions($event, $session, $data);
        }

        return $session;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateRecurringSessions(Event $event, EventSession $template, array $data): void
    {
        $dates = Event::expandRecurrenceRule(
            $data['recurrence_rule'],
            $template->start_date,
            $template->end_date->copy()->addYear(),
        );

        // Skip the first date (it's the template session itself)
        array_shift($dates);

        foreach ($dates as $index => $date) {
            $duration = $template->start_date->diffInSeconds($template->end_date);
            $endDate = (clone $date)->addSeconds($duration);

            $event->sessions()->create([
                'title' => $template->title,
                'start_date' => $date,
                'end_date' => $endDate,
                'venue_id' => $template->venue_id,
                'location' => $template->location,
                'capacity' => $template->capacity,
                'sort_order' => $template->sort_order + $index + 1,
            ]);
        }
    }

    /** @return Collection<int, Event> */
    public function getPublicEvents(): Collection
    {
        return $this->repository->getPublished();
    }

    /** @return Collection<int, Event> */
    public function getFeaturedEvents(): Collection
    {
        return $this->repository->getFeatured();
    }

    /** @return Collection<int, Event> */
    public function getTrendingEvents(): Collection
    {
        return $this->repository->getTrending();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Event>
     */
    public function searchEvents(array $filters): LengthAwarePaginator
    {
        return $this->repository->search($filters);
    }

    public function joinWaitingList(Event $event, User $user): WaitingListEntry
    {
        return WaitingListEntry::firstOrCreate([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function leaveWaitingList(Event $event, User $user): void
    {
        WaitingListEntry::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function isOnWaitingList(Event $event, User $user): bool
    {
        return WaitingListEntry::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
