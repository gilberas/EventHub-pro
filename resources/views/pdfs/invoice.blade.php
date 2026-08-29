<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; line-height: 1.6; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; color: #1a202c; margin: 0; }
        .header p { font-size: 14px; color: #718096; margin: 5px 0; }
        .details { margin-bottom: 20px; }
        .details table { width: 100%; }
        .details td { vertical-align: top; padding: 5px; }
        .details .label { font-weight: bold; color: #4a5568; width: 120px; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th { background: #edf2f7; padding: 10px 8px; text-align: left; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #4a5568; }
        table.items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        table.items .right { text-align: right; }
        .totals { margin-top: 20px; text-align: right; }
        .totals table { margin-left: auto; width: 300px; }
        .totals td { padding: 4px 8px; }
        .totals .grand-total { font-size: 16px; font-weight: bold; color: #2d3748; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #a0aec0; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice</h1>
        <p>{{ $invoice->number }}</p>
        <p>{{ $invoice->issued_at ? $invoice->issued_at->format('F j, Y') : '' }}</p>
    </div>

    <div class="details">
        <table>
            <tr>
                <td class="label">Customer:</td>
                <td>{{ $invoice->booking?->user?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td>{{ $invoice->booking?->user?->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Booking Ref:</td>
                <td>{{ $invoice->booking?->reference ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Event:</td>
                <td>{{ $invoice->booking?->eventSession?->event?->title ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->booking?->items ?? [] as $item)
                <tr>
                    <td>{{ $item->ticketType?->name ?? 'Ticket' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="right">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if ((float) $invoice->discount_total > 0)
                <tr>
                    <td>Discount:</td>
                    <td class="right">-{{ number_format((float) $invoice->discount_total, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td>Fees:</td>
                <td class="right">{{ number_format((float) $invoice->fees, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total:</td>
                <td class="right">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>EventHub Pro &mdash; Thank you for your business!</p>
        <p>Invoice {{ $invoice->number }} &mdash; {{ $invoice->status }}</p>
    </div>
</body>
</html>
