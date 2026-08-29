<?php

declare(strict_types=1);

namespace App\Domain\Events\Models;

use App\Models\User;
use Database\Factories\WaitingListEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property Carbon|null $notified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read User $user
 */
class WaitingListEntry extends Model
{
    /** @use HasFactory<WaitingListEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsNotified(): void
    {
        $this->update(['notified_at' => now()]);
    }

    public function isNotified(): bool
    {
        return $this->notified_at !== null;
    }
}
