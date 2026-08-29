<?php

declare(strict_types=1);

namespace App\Domain\Bookings\Models;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Payments\Models\Invoice;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Domain\Payments\Models\RefundRequest;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Booking extends Model implements Auditable
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'user_id',
        'event_session_id',
        'reference',
        'status',
        'subtotal',
        'fees',
        'total',
        'currency',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'subtotal' => 'decimal:2',
            'fees' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<EventSession, $this> */
    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    /** @return HasMany<BookingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /** @return HasMany<BookingSeat, $this> */
    public function seats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /** @return HasMany<PaymentTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'payable_id')
            ->where('payable_type', static::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<RefundRequest, $this> */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public static function generateReference(): string
    {
        $prefix = 'BK-';
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        return $prefix.$random;
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_session_id', 'id')
            ->through('eventSession');
    }
}
