<?php

declare(strict_types=1);

namespace App\Domain\Events\Models;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\EventStatus;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $category
 * @property string[]|null $tags
 * @property EventStatus $status
 * @property int|null $age_restriction
 * @property string|null $terms
 * @property int|null $refund_policy_days
 * @property float|null $refund_policy_percentage
 * @property bool $is_featured
 * @property float|null $trending_score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Organization $organization
 * @property-read Collection<int, EventSession> $sessions
 * @property-read Collection<int, EventSession> $upcomingSessions
 * @property-read EventSession|null $nextSession
 */
class Event extends Model implements Auditable, HasMedia
{
    /** @use HasFactory<EventFactory> */
    use BelongsToOrganization, HasFactory, InteractsWithMedia, SoftDeletes;

    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'description',
        'category',
        'tags',
        'status',
        'age_restriction',
        'terms',
        'refund_policy_days',
        'refund_policy_percentage',
        'is_featured',
        'trending_score',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'status' => EventStatus::class,
            'age_restriction' => 'integer',
            'refund_policy_days' => 'integer',
            'refund_policy_percentage' => 'decimal:2',
            'is_featured' => 'boolean',
            'trending_score' => 'float',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('gallery')
            ->useDisk('public');

        $this->addMediaCollection('video')
            ->singleFile()
            ->useDisk('public');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<EventSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('start_date');
    }

    /** @return HasMany<EventSession, $this> */
    public function upcomingSessions(): HasMany
    {
        return $this->sessions()->where('start_date', '>=', now())->orderBy('start_date');
    }

    public function nextSession(): ?EventSession
    {
        return $this->upcomingSessions()->first();
    }

    /** @return HasMany<WaitingListEntry, $this> */
    public function waitingList(): HasMany
    {
        return $this->hasMany(WaitingListEntry::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_events')
            ->withPivot('created_at');
    }

    public function isFavoritedBy(?User $user): bool
    {
        return $user !== null && $this->relationLoaded('favoritedBy')
            ? $this->favoritedBy->contains('id', $user->id)
            : $this->favoritedBy()->where('users.id', $user?->id)->exists();
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === EventStatus::Draft;
    }

    public function isSoldOut(): bool
    {
        return ! $this->sessions->some(fn ($s) => $s->availableTickets() > 0);
    }

    public function hasAvailableTickets(): bool
    {
        return $this->sessions->some(fn ($s) => $s->availableTickets() > 0);
    }

    public function coverUrl(): ?string
    {
        return $this->getFirstMedia('cover')?->getFullUrl();
    }

    /** @return string[] */
    public function galleryUrls(): array
    {
        return $this->getMedia('gallery')->map(fn ($m) => $m->getFullUrl())->toArray();
    }

    /** @param array<string, mixed> $data */
    public static function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $base = str($title)->slug()->toString();
        $slug = $base;
        $counter = 1;

        while (true) {
            $query = static::where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /** @return array<int, Carbon> */
    public static function expandRecurrenceRule(string $rrule, Carbon $start, Carbon $end, int $limit = 10): array
    {
        // PLACEHOLDER: Basic RRULE parser for v1 supporting FREQ=DAILY|WEEKLY|MONTHLY and INTERVAL.
        // Full iCalendar RRULE support (BYDAY, BYMONTHDAY, COUNT, UNTIL) can be added later.
        $dates = [];
        $current = $start->copy();
        $interval = 1;
        $freq = 'DAILY';

        foreach (explode(';', $rrule) as $part) {
            $parts = explode('=', $part);
            if (count($parts) !== 2) {
                continue;
            }
            if ($parts[0] === 'FREQ') {
                $freq = $parts[1];
            } elseif ($parts[0] === 'INTERVAL') {
                $interval = (int) $parts[1];
            }
        }

        $count = 0;
        while ($count < $limit && $current <= $end) {
            $dates[] = $current->copy();
            $count++;

            match ($freq) {
                'DAILY' => $current->addDays($interval),
                'WEEKLY' => $current->addWeeks($interval),
                'MONTHLY' => $current->addMonths($interval),
                default => $current->addDays($interval),
            };
        }

        return $dates;
    }
}
