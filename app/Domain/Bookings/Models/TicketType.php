<?php

declare(strict_types=1);

namespace App\Domain\Bookings\Models;

use App\Domain\Events\Models\EventSession;
use App\Shared\Enums\TicketMode;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'event_session_id',
        'name',
        'mode',
        'price',
        'quantity_available',
        'max_per_order',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'mode' => TicketMode::class,
            'price' => 'decimal:2',
            'quantity_available' => 'integer',
            'max_per_order' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<EventSession, $this> */
    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function isReserved(): bool
    {
        return $this->mode === TicketMode::Reserved;
    }

    public function isGA(): bool
    {
        return $this->mode === TicketMode::GeneralAdmission;
    }
}
