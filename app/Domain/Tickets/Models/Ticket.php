<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Models;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Events\Models\EventSession;
use App\Domain\Venues\Models\Seat;
use App\Models\User;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'event_session_id',
        'ticket_type_id',
        'seat_id',
        'ticket_number',
        'qr_payload',
        'status',
        'holder_name',
        'holder_email',
        'checked_in_at',
        'checked_in_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(TicketScanLog::class);
    }

    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT-';
        $random = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));

        return $prefix.$random;
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }
}
