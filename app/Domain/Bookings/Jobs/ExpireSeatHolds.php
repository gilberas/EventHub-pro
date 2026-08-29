<?php

declare(strict_types=1);

namespace App\Domain\Bookings\Jobs;

use App\Domain\Bookings\Models\SeatHold;
use App\Shared\Enums\SeatHoldStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ExpireSeatHolds implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DB::transaction(function () {
            $expired = SeatHold::with('ticketType')
                ->where('status', SeatHoldStatus::Active)
                ->where('expires_at', '<', now())
                ->lockForUpdate()
                ->get();

            foreach ($expired as $hold) {
                // General-admission holds decrement the ticket type's
                // quantity_available at hold time; give it back on expiry.
                // Reserved-seat holds use the seat itself as inventory.
                if ($hold->seat_id === null && $hold->ticketType !== null && $hold->ticketType->quantity_available !== null) {
                    $hold->ticketType->increment('quantity_available', (int) $hold->quantity);
                }

                $hold->update(['status' => SeatHoldStatus::Expired]);
            }
        });
    }
}
