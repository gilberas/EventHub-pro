<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Payments\Events\BookingInvoiceGenerated;
use App\Domain\Payments\Models\Invoice;
use App\Services\PdfRenderer;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generate(Booking $booking, float $discountTotal = 0): Invoice
    {
        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'number' => Invoice::generateNumber(),
            'status' => 'issued',
            'subtotal' => (float) $booking->subtotal,
            'discount_total' => $discountTotal,
            'fees' => (float) $booking->fees,
            'total' => (float) $booking->total,
            'currency' => $booking->currency,
            'issued_at' => now(),
            'paid_at' => $booking->paid_at ?? now(),
        ]);

        $pdfPath = $this->generatePdf($invoice);

        $invoice->update(['pdf_path' => $pdfPath]);

        BookingInvoiceGenerated::dispatch($booking, $invoice);

        return $invoice->fresh();
    }

    public function generatePdf(Invoice $invoice): string
    {
        $pdf = app(PdfRenderer::class)
            ->view('pdfs.invoice')
            ->data(['invoice' => $invoice->load('booking.user', 'booking.items', 'booking.eventSession.event')]);

        $filename = sprintf('invoices/%s.pdf', $invoice->number);
        $path = Storage::disk('local')->path($filename);

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->save($path);

        return $filename;
    }
}
