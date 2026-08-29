<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Models;

use App\Domain\Events\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketScanLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'scanned_by_user_id',
        'event_session_id',
        'result',
        'raw_payload',
        'error_message',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }
}
