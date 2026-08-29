<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tickets - {{ $booking->reference }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; line-height: 1.5; color: #333; }
        .page { width: 100%; }
        .ticket { border: 2px solid #1a202c; border-radius: 12px; padding: 20px; margin-bottom: 20px; page-break-inside: avoid; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #cbd5e1; }
        .org-name { font-size: 18px; font-weight: bold; color: #1a202c; }
        .ticket-number { font-size: 12px; color: #718096; }
        .event-title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .event-details { font-size: 11px; color: #4a5568; margin-bottom: 8px; }
        .details-grid { display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0; }
        .detail-item { flex: 1; min-width: 120px; }
        .detail-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; }
        .detail-value { font-size: 13px; font-weight: 600; color: #2d3748; }
        .qr-container { text-align: center; margin: 15px 0; }
        .qr-container img { width: 120px; height: 120px; }
        .footer { font-size: 9px; color: #a0aec0; text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; }
        .seat-info { background: #f7fafc; border-radius: 6px; padding: 8px 12px; display: inline-block; margin-top: 5px; }
        .cut-line { text-align: center; color: #cbd5e1; font-size: 10px; margin: 5px 0; }
    </style>
</head>
<body>
    @php
        $org = $booking->eventSession?->event?->organization;
        $event = $booking->eventSession?->event;
        $session = $booking->eventSession;
    @endphp

    @foreach ($tickets as $ticket)
        <div class="ticket">
            <div class="header">
                <div>
                    <div class="org-name">{{ $org?->name ?? 'EventHub Pro' }}</div>
                    <div class="ticket-number">{{ $ticket->ticket_number }}</div>
                </div>
                <div class="ticket-number">#{{ $ticket->id }}</div>
            </div>

            <div class="event-title">{{ $event?->title ?? 'Event' }}</div>
            <div class="event-details">
                {{ $session?->start_date?->format('l, F j, Y g:i A') ?? '' }}
                @if ($session?->location)
                    &mdash; {{ $session->location }}
                @endif
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Ticket Type</div>
                    <div class="detail-value">{{ $ticket->bookingItem?->ticketType?->name ?? 'General Admission' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Holder</div>
                    <div class="detail-value">{{ $ticket->holder_name ?? $booking->user?->name ?? 'N/A' }}</div>
                </div>
                @if ($ticket->seat)
                    <div class="detail-item">
                        <div class="detail-label">Seat</div>
                        <div class="detail-value seat-info">
                            {{ $ticket->seat?->section?->row?->label ?? '' }}
                            {{ $ticket->seat?->label ?? '' }}
                        </div>
                    </div>
                @endif
                <div class="detail-item">
                    <div class="detail-label">Booking Ref</div>
                    <div class="detail-value">{{ $booking->reference }}</div>
                </div>
            </div>

            @if($ticket->qr_payload)
                @php
                    try {
                        $qrCode = (new \chillerlan\QRCode\QRCode)->render($ticket->qr_payload);
                    } catch (\Throwable $e) {
                        $qrCode = '';
                    }
                @endphp
                @if($qrCode)
                    <div class="qr-container">
                        <img src="{{ $qrCode }}" alt="QR Code" />
                    </div>
                @endif
            @endif

            <div class="footer">
                <p>{{ $org?->name ?? 'EventHub Pro' }} &mdash; Present this ticket for entry</p>
                <p>Ticket {{ $ticket->ticket_number }}</p>
            </div>
        </div>
        @if (!$loop->last)
            <div class="cut-line">- - - - - - - - - - - - - - - - - - - - - - - - - - - - -</div>
        @endif
    @endforeach
</body>
</html>
