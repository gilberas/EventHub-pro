import DashboardLayout from '@/Layouts/DashboardLayout';
import { Link, Head } from '@inertiajs/react';
import { Calendar, CheckCircle2, MapPin, Ticket as TicketIcon } from 'lucide-react';

interface TicketRow {
    id: number;
    ticket_number: string;
    qr_payload: string;
    status: string;
    checked_in_at: string | null;
    created_at: string;
    event_session: {
        id: number;
        title: string | null;
        start_date: string;
        location: string | null;
        event: { id: number; title: string; slug: string };
    };
    booking: { id: number; reference: string } | null;
    booking_item: { ticket_type: { id: number; name: string; price: number } } | null;
    seat: {
        id: number;
        seat_number: string;
        row: { id: number; label: string; section: { id: number; name: string } | null } | null;
    } | null;
}

export default function MyTickets({ tickets }: { tickets: TicketRow[] }) {
    const activeTickets = tickets.filter((t) => t.status === 'active');
    const usedTickets = tickets.filter((t) => t.status === 'used');

    return (
        <DashboardLayout>
            <Head title="My Tickets" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    My Tickets
                </h1>
                <p className="text-muted-foreground">
                    Your event tickets and QR codes for check-in.
                </p>
            </div>

            {activeTickets.length === 0 && usedTickets.length === 0 ? (
                <div className="border-border text-muted-foreground rounded-lg border-2 border-dashed p-12 text-center">
                    <TicketIcon className="mx-auto mb-2 h-8 w-8" />
                    <p>You don't have any tickets yet.</p>
                    <Link
                        href="/events/search"
                        className="text-primary mt-2 inline-block text-sm underline"
                    >
                        Browse events
                    </Link>
                </div>
            ) : (
                <div className="space-y-6">
                    {activeTickets.length > 0 && (
                        <section>
                            <h2 className="mb-3 text-lg font-semibold">
                                Active
                            </h2>
                            <div className="grid gap-4 md:grid-cols-2">
                                {activeTickets.map((ticket) => (
                                    <TicketCard
                                        key={ticket.id}
                                        ticket={ticket}
                                    />
                                ))}
                            </div>
                        </section>
                    )}

                    {usedTickets.length > 0 && (
                        <section>
                            <h2 className="mb-3 text-lg font-semibold">
                                Used
                            </h2>
                            <div className="grid gap-4 md:grid-cols-2">
                                {usedTickets.map((ticket) => (
                                    <TicketCard
                                        key={ticket.id}
                                        ticket={ticket}
                                    />
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            )}
        </DashboardLayout>
    );
}

function TicketCard({ ticket }: { ticket: TicketRow }) {
    const isUsed = ticket.checked_in_at !== null;
    const event = ticket.event_session.event;
    const ticketType = ticket.booking_item?.ticket_type;
    const seat = ticket.seat;

    return (
        <div className="border-border bg-card overflow-hidden rounded-lg border">
            <div className="flex items-stretch">
                <div className="flex-1 p-4">
                    <div className="mb-2 flex items-start justify-between gap-2">
                        <div>
                            <Link
                                href={route('events.show', event.slug)}
                                className="font-semibold hover:underline"
                            >
                                {event.title}
                            </Link>
                            <p className="text-muted-foreground text-sm">
                                {ticket.event_session.title ?? 'Main Session'}
                            </p>
                        </div>
                        {isUsed ? (
                            <span className="flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                <CheckCircle2 className="h-3 w-3" />
                                Checked in
                            </span>
                        ) : (
                            <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                Active
                            </span>
                        )}
                    </div>

                    <div className="text-muted-foreground space-y-1 text-sm">
                        <p className="flex items-center gap-1.5">
                            <Calendar className="h-3.5 w-3.5" />
                            {new Date(
                                ticket.event_session.start_date,
                            ).toLocaleString()}
                        </p>
                        {ticket.event_session.location && (
                            <p className="flex items-center gap-1.5">
                                <MapPin className="h-3.5 w-3.5" />
                                {ticket.event_session.location}
                            </p>
                        )}
                        <p>{ticketType?.name}</p>
                        {seat && (
                            <p>
                                Seat {seat.row?.label}
                                {seat.seat_number}
                                {seat.row?.section?.name &&
                                    ` — ${seat.row.section.name}`}
                            </p>
                        )}
                    </div>

                    <div className="text-muted-foreground mt-3 flex items-center justify-between border-t pt-3 text-xs">
                        <span>{ticket.ticket_number}</span>
                        {isUsed && (
                            <span>
                                Used{' '}
                                {new Date(
                                    ticket.checked_in_at!,
                                ).toLocaleString()}
                            </span>
                        )}
                    </div>
                </div>

                {!isUsed && ticket.qr_payload && (
                    <div className="flex flex-col items-center justify-center border-l p-3">
                        <img
                            src={route('tickets.qr', ticket.id)}
                            alt={`QR code for ${ticket.ticket_number}`}
                            className="h-28 w-28"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}