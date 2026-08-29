import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link } from '@inertiajs/react';
import { Calendar, MapPin, Ticket } from 'lucide-react';

interface BookingEvent {
    id: number;
    title: string;
    slug: string;
    organization_name: string | null;
}

interface BookingSession {
    id: number;
    start_date: string;
    location: string | null;
    event: BookingEvent | null;
}

interface BookingItem {
    id: number;
    ticket_type_id: number;
    quantity: number;
    unit_price: number;
    ticketType: { id: number; name: string } | null;
}

interface Booking {
    id: number;
    reference: string;
    status: string;
    total: number;
    currency: string;
    created_at: string;
    event_session_id: number;
    eventSession: BookingSession | null;
    items: BookingItem[] | null;
}

interface Props {
    bookings: Booking[];
}

export default function BookingsIndex({ bookings }: Props) {
    const statusColors: Record<string, string> = {
        pending_payment: 'bg-yellow-100 text-yellow-700',
        confirmed: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        refunded: 'bg-gray-100 text-gray-700',
    };

    return (
        <PublicLayout>
            <Head title="My Bookings" />

            <div className="mx-auto max-w-4xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold tracking-tight">
                    My Bookings
                </h1>

                {bookings.length === 0 ? (
                    <div className="border-border rounded-lg border-2 border-dashed p-12 text-center">
                        <Ticket className="text-muted-foreground mx-auto mb-2 h-8 w-8" />
                        <p className="text-muted-foreground">
                            No bookings yet.
                        </p>
                        <Link
                            href={route('events.search')}
                            className="text-primary mt-2 inline-block text-sm hover:underline"
                        >
                            Browse events
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {bookings.map((booking) => (
                            <Link
                                key={booking.id}
                                href={route('bookings.show', booking.reference)}
                                className="border-border bg-card hover:border-primary block rounded-lg border transition-colors"
                            >
                                <div className="p-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="font-semibold">
                                                {booking.eventSession?.event
                                                    ?.title ?? 'Event'}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Ref: {booking.reference}
                                            </p>
                                            {booking.eventSession
                                                ?.start_date && (
                                                <p className="text-muted-foreground mt-1 flex items-center gap-1 text-xs">
                                                    <Calendar className="h-3 w-3" />
                                                    {new Date(
                                                        booking.eventSession
                                                            .start_date,
                                                    ).toLocaleDateString()}
                                                </p>
                                            )}
                                            {booking.eventSession?.location && (
                                                <p className="text-muted-foreground flex items-center gap-1 text-xs">
                                                    <MapPin className="h-3 w-3" />
                                                    {
                                                        booking.eventSession
                                                            .location
                                                    }
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex flex-col items-end gap-1">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${statusColors[booking.status] ?? 'bg-gray-100 text-gray-700'}`}
                                            >
                                                {booking.status.replace(
                                                    '_',
                                                    ' ',
                                                )}
                                            </span>
                                            <span className="text-sm font-semibold">
                                                $
                                                {Number(booking.total).toFixed(
                                                    2,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                    {booking.items &&
                                        booking.items.length > 0 && (
                                            <div className="border-border text-muted-foreground mt-3 flex flex-wrap gap-2 border-t pt-3 text-xs">
                                                {booking.items.map((item) => (
                                                    <span key={item.id}>
                                                        {item.quantity}x{' '}
                                                        {item.ticketType
                                                            ?.name ?? 'Ticket'}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
