<?php

declare(strict_types=1);

namespace App\Domain\Events\DTOs;

use App\Domain\Events\Models\Event;
use App\Shared\Enums\EventStatus;

class EventDTO
{
    /**
     * @param  string[]|null  $tags
     * @param  string[]  $gallery_urls
     * @param  array<int, array<string, mixed>>|null  $sessions
     */
    public function __construct(
        public readonly int $id,
        public readonly int $organization_id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $category,
        public readonly ?array $tags,
        public readonly EventStatus $status,
        public readonly ?int $age_restriction,
        public readonly ?string $terms,
        public readonly ?int $refund_policy_days,
        public readonly ?float $refund_policy_percentage,
        public readonly bool $is_featured,
        public readonly ?float $trending_score,
        public readonly ?string $cover_url,
        public readonly array $gallery_urls,
        public readonly ?string $organization_name,
        public readonly ?string $next_session_date,
        public readonly ?array $sessions = null,
    ) {}

    public static function fromModel(Event $event): self
    {
        $sessions = null;

        if ($event->relationLoaded('sessions')) {
            $sessions = $event->sessions->map(function ($s) {
                $ticketTypes = null;

                if ($s->relationLoaded('ticketTypes')) {
                    $ticketTypes = $s->ticketTypes->map(fn ($tt) => [
                        'id' => $tt->id,
                        'name' => $tt->name,
                        'mode' => $tt->mode->value,
                        'price' => (float) $tt->price,
                        'quantity_available' => $tt->quantity_available,
                        'max_per_order' => $tt->max_per_order,
                        'sort_order' => $tt->sort_order,
                    ])->toArray();
                }

                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'start_date' => $s->start_date->toIso8601String(),
                    'end_date' => $s->end_date->toIso8601String(),
                    'venue_id' => $s->venue_id,
                    'location' => $s->location,
                    'capacity' => $s->capacity,
                    'available_tickets' => $s->availableTickets(),
                    'ticket_types' => $ticketTypes,
                ];
            })->toArray();
        }

        return new self(
            id: $event->id,
            organization_id: $event->organization_id,
            title: $event->title,
            slug: $event->slug,
            description: $event->description,
            category: $event->category,
            tags: $event->tags,
            status: $event->status,
            age_restriction: $event->age_restriction,
            terms: $event->terms,
            refund_policy_days: $event->refund_policy_days,
            refund_policy_percentage: $event->refund_policy_percentage,
            is_featured: $event->is_featured,
            trending_score: $event->trending_score,
            cover_url: $event->coverUrl(),
            gallery_urls: $event->galleryUrls(),
            organization_name: $event->relationLoaded('organization') ? $event->organization->name : null,
            next_session_date: $event->nextSession()?->start_date?->toIso8601String(),
            sessions: $sessions,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'tags' => $this->tags,
            'status' => $this->status->value,
            'age_restriction' => $this->age_restriction,
            'terms' => $this->terms,
            'refund_policy_days' => $this->refund_policy_days,
            'refund_policy_percentage' => $this->refund_policy_percentage,
            'is_featured' => $this->is_featured,
            'trending_score' => $this->trending_score,
            'cover_url' => $this->cover_url,
            'gallery_urls' => $this->gallery_urls,
            'organization_name' => $this->organization_name,
            'next_session_date' => $this->next_session_date,
            'sessions' => $this->sessions,
        ];
    }
}
