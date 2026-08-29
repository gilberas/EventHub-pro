<?php

declare(strict_types=1);

namespace App\Domain\Bookings\Models;

use App\Domain\Events\Models\EventSession;
use App\Domain\Venues\Models\Seat;
use App\Models\User;
use App\Shared\Enums\SeatHoldStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatHold extends Model
{
    protected $fillable = [
        'event_session_id',
        'ticket_type_id',
        'seat_id',
        'user_id',
        'quantity',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SeatHoldStatus::class,
            'expires_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<EventSession, $this> */
    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /** @return BelongsTo<Seat, $this> */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
