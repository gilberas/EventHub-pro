<?php

declare(strict_types=1);

namespace App\Domain\Payments\Events;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Payments\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

class BookingInvoiceGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly Booking $booking,
        public readonly Invoice $invoice,
    ) {}
}
