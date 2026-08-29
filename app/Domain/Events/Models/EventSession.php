<?php

declare(strict_types=1);

namespace App\Domain\Events\Models;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingSeat;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Venues\Models\Venue;
use App\Shared\Enums\SeatHoldStatus;
use Database\Factories\EventSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int|null $venue_id
 * @property string|null $title
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string|null $location
 * @property int|null $capacity
 * @property string|null $recurrence_rule
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read Venue|null $venue
 */
class EventSession extends Model
{
    /** @use HasFactory<EventSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'venue_id',
        'title',
        'start_date',
        'end_date',
        'location',
        'capacity',
        'recurrence_rule',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'capacity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<TicketType, $this> */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('sort_order');
    }

    public function availableTickets(): int
    {
        if ($this->capacity === null) {
            return 999999;
        }

        $booked = BookingSeat::where('event_session_id', $this->id)->count();
        $held = SeatHold::where('event_session_id', $this->id)
            ->where('status', SeatHoldStatus::Active)
            ->where('expires_at', '>', now())
            ->count();

        return $this->capacity - $booked - $held;
    }
}
