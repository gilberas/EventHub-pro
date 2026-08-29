<?php

namespace App\Providers;

use App\Domain\Payments\Events\BookingInvoiceGenerated;
use App\Domain\Tickets\Listeners\GenerateTicketsListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingInvoiceGenerated::class => [
            GenerateTicketsListener::class,
        ],
    ];
}
