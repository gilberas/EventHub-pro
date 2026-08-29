<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1a202c;">Your Tickets Are Ready!</h1>
    </div>

    <p>Hi {{ $user->name }},</p>

    <p>Your tickets for <strong>{{ $booking->eventSession?->event?->title ?? 'Event' }}</strong> are attached to this email.</p>

    <div style="background: #f7fafc; border-radius: 8px; padding: 15px; margin: 20px 0;">
        <p style="margin: 0 0 8px;"><strong>Booking Reference:</strong> {{ $booking->reference }}</p>
        <p style="margin: 0 0 8px;"><strong>Date:</strong> {{ $booking->eventSession?->start_date?->format('l, F j, Y g:i A') ?? 'N/A' }}</p>
        <p style="margin: 0;"><strong>Status:</strong> Confirmed</p>
    </div>

    <p>Please present the attached PDF tickets at the event entrance for scanning.</p>

    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #a0aec0;">
        <p>EventHub Pro &mdash; Seamless Event Management</p>
    </div>
</body>
</html>
