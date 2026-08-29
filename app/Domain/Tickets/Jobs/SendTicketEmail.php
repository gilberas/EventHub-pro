<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Jobs;

use App\Domain\Bookings\Models\Booking;
use App\Services\PdfRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendTicketEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly array $tickets,
    ) {}

    public function handle(): void
    {
        $pdfPath = $this->generateTicketsPdf();

        $user = $this->booking->user;

        try {
            Mail::send('emails.tickets-confirmation', [
                'booking' => $this->booking,
                'user' => $user,
            ], function ($message) use ($pdfPath, $user) {
                $message->to($user->email, $user->name)
                    ->subject('Your Tickets for '.($this->booking->eventSession?->event?->title ?? 'Event'))
                    ->attach(Storage::disk('local')->path($pdfPath), [
                        'as' => 'tickets-'.$this->booking->reference.'.pdf',
                        'mime' => 'application/pdf',
                    ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send ticket email for booking '.$this->booking->reference, [
                'error' => $e->getMessage(),
                'booking_id' => $this->booking->id,
            ]);
        }
    }

    private function generateTicketsPdf(): string
    {
        $this->booking->loadMissing('eventSession.event.organization', 'user', 'items.ticketType');

        $pdf = app(PdfRenderer::class)
            ->view('pdfs.tickets')
            ->data([
                'booking' => $this->booking,
                'tickets' => collect($this->tickets)->load('seat.section.row', 'bookingItem.ticketType'),
            ]);

        $filename = sprintf('tickets/%s.pdf', $this->booking->reference);
        $path = Storage::disk('local')->path($filename);

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->save($path);

        return $filename;
    }
}
