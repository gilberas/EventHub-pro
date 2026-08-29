import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    CheckCircle,
    Clock,
    MapPin,
    Ticket,
} from 'lucide-react';

interface BookingSeat {
    id: number;
    seat_id: number;
    seat: {
        id: number;
        seat_number: number;
        type: string;
        row: { label: string; section?: { name: string; color?: string } };
    } | null;
}

interface BookingTicket {
    id: number;
    ticket_number: string;
    qr_payload: string;
    status: string;
    checked_in_at: string | null;
    booking_item: {
        id: number;
        ticket_type: { id: number; name: string } | null;
    } | null;
    seat: {
        id: number;
        seat_number: number;
        row: { label: string; section?: { name: string } } | null;
    } | null;
}

interface BookingItem {
    id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    ticketType: { id: number; name: string; price: number } | null;
}

interface BookingEvent {
    id: number;
    title: string;
    slug: string;
    cover_url: string | null;
    organization_name: string | null;
}

interface BookingSession {
    id: number;
    start_date: string;
    end_date: string;
    location: string | null;
    event: BookingEvent | null;
}

interface Booking {
    id: number;
    reference: string;
    status: string;
    subtotal: number;
    fees: number;
    total: number;
    currency: string;
    notes: string | null;
    paid_at: string | null;
    created_at: string;
    eventSession: BookingSession | null;
    items: BookingItem[] | null;
    seats: BookingSeat[] | null;
    tickets: BookingTicket[] | null;
}

interface Props {
    booking: Booking;
}

export default function BookingShow({ booking }: Props) {
    const event = booking.eventSession?.event;
    const statusColors: Record<string, string> = {
        pending_payment: 'bg-yellow-100 text-yellow-700',
        confirmed: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        refunded: 'bg-gray-100 text-gray-700',
    };

    return (
        <PublicLayout>
            <Head title={`Booking ${booking.reference}`} />

            <div className="mx-auto max-w-3xl px-4 py-8">
                <Link
                    href={route('bookings.index')}
                    className="text-muted-foreground hover:text-foreground mb-4 flex items-center gap-1 text-sm"
                >
                    <ArrowLeft className="h-4 w-4" />
                    My Bookings
                </Link>

                <div className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Booking {booking.reference}
                    </h1>
                    <span
                        className={`mt-1 inline-block rounded-full px-3 py-1 text-xs font-medium ${statusColors[booking.status] ?? ''}`}
                    >
                        {booking.status.replace('_', ' ')}
                    </span>
                </div>

                {booking.status === 'pending_payment' && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-700">
                        <AlertTriangle className="h-5 w-5" />
                        <span>
                            Payment is pending. Complete your payment to confirm
                            the booking.
                        </span>
                    </div>
                )}

                {booking.status === 'confirmed' && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg bg-green-50 p-4 text-sm text-green-700">
                        <CheckCircle className="h-5 w-5" />
                        <span>Your booking is confirmed! Enjoy the event.</span>
                    </div>
                )}

                <div className="space-y-6">
                    {event && (
                        <div className="border-border bg-card rounded-lg border p-4">
                            <h2 className="mb-3 font-semibold">
                                Event Details
                            </h2>
                            {event.cover_url && (
                                <img
                                    src={event.cover_url}
                                    alt={event.title}
                                    className="mb-3 h-40 w-full rounded-lg object-cover"
                                />
                            )}
                            <h3 className="font-medium">{event.title}</h3>
                            <p className="text-muted-foreground text-sm">
                                {event.organization_name}
                            </p>
                            {booking.eventSession && (
                                <div className="text-muted-foreground mt-2 space-y-1 text-sm">
                                    <p className="flex items-center gap-1">
                                        <Calendar className="h-4 w-4" />
                                        {new Date(
                                            booking.eventSession.start_date,
                                        ).toLocaleDateString()}
                                    </p>
                                    <p className="flex items-center gap-1">
                                        <Clock className="h-4 w-4" />
                                        {new Date(
                                            booking.eventSession.start_date,
                                        ).toLocaleTimeString()}{' '}
                                        –{' '}
                                        {new Date(
                                            booking.eventSession.end_date,
                                        ).toLocaleTimeString()}
                                    </p>
                                    {booking.eventSession.location && (
                                        <p className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4" />
                                            {booking.eventSession.location}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    <div className="border-border bg-card rounded-lg border p-4">
                        <h2 className="mb-3 font-semibold">Tickets</h2>
                        {booking.items?.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-center justify-between py-2"
                            >
                                <div className="flex items-center gap-2">
                                    <Ticket className="text-muted-foreground h-4 w-4" />
                                    <span className="text-sm">
                                        {item.quantity}x{' '}
                                        {item.ticketType?.name ?? 'Ticket'}
                                    </span>
                                </div>
                                <span className="text-sm font-medium">
                                    ${Number(item.subtotal).toFixed(2)}
                                </span>
                            </div>
                        ))}

                        {booking.seats && booking.seats.length > 0 && (
                            <div className="border-border mt-3 border-t pt-3">
                                <p className="text-muted-foreground mb-2 text-xs font-medium">
                                    Assigned Seats
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {booking.seats.map((bs) => (
                                        <span
                                            key={bs.id}
                                            className="bg-primary/10 text-primary rounded-md px-2 py-1 text-xs font-medium"
                                        >
                                            {bs.seat?.row?.label}
                                            {bs.seat?.seat_number}
                                            {bs.seat?.row?.section?.name &&
                                                ` (${bs.seat.row.section.name})`}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}

                        {booking.tickets && booking.tickets.length > 0 && (
                            <div className="border-border mt-4 gap-4 border-t pt-3">
                                <p className="text-muted-foreground mb-2 text-xs font-medium">
                                    Digital Tickets
                                </p>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {booking.tickets.map((ticket) => (
                                        <div
                                            key={ticket.id}
                                            className="border-border flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <div className="text-sm">
                                                <p className="font-medium">
                                                    {ticket.ticket_number}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {
                                                        ticket.booking_item
                                                            ?.ticket_type?.name
                                                    }
                                                    {ticket.seat &&
                                                        ` · Seat ${ticket.seat.row?.label}${ticket.seat.seat_number}`}
                                                </p>
                                                {ticket.checked_in_at && (
                                                    <p className="text-xs text-emerald-600">
                                                        Checked in
                                                    </p>
                                                )}
                                            </div>
                                            {ticket.qr_payload &&
                                                ticket.status === 'active' && (
                                                    <img
                                                        src={route(
                                                            'tickets.qr',
                                                            ticket.id,
                                                        )}
                                                        alt={`QR for ${ticket.ticket_number}`}
                                                        className="h-16 w-16"
                                                    />
                                                )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="border-border bg-card rounded-lg border p-4">
                        <h2 className="mb-3 font-semibold">Summary</h2>
                        <div className="space-y-1 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Subtotal
                                </span>
                                <span>
                                    ${Number(booking.subtotal).toFixed(2)}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Fees
                                </span>
                                <span>${Number(booking.fees).toFixed(2)}</span>
                            </div>
                            <div className="border-border flex justify-between border-t pt-2 text-base font-bold">
                                <span>Total</span>
                                <span>${Number(booking.total).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
