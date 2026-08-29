import PublicLayout from '@/Layouts/PublicLayout';
import { cn } from '@/Lib/utils';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Clock,
    MapPin,
    ShoppingCart,
    Users,
} from 'lucide-react';
import { useState } from 'react';

interface Seat {
    id: number;
    seat_number: number;
    type: string;
    row_position: number;
    col_position: number;
    is_active: boolean;
}

interface RowData {
    id: number;
    label: string;
    sort_order: number;
    seats: Seat[];
}

interface SectionData {
    id: number;
    name: string;
    section_type: string;
    color: string | null;
    rows: RowData[];
}

interface HallData {
    id: number;
    name: string;
    sections: SectionData[];
}

interface TicketTypeOption {
    id: number;
    name: string;
    mode: string;
    price: number;
    quantity_available: number | null;
    max_per_order: number;
}

interface EventData {
    id: number;
    title: string;
    slug: string;
    organization_name: string | null;
}

interface SessionData {
    id: number;
    title: string | null;
    start_date: string;
    end_date: string;
    location: string | null;
    capacity: number | null;
    available_tickets: number;
}

interface Props {
    event: EventData;
    session: SessionData;
    hall: HallData | null;
    seatStatus: { booked: number[]; held: number[] };
    ticketTypes: TicketTypeOption[];
}

const seatTypeColors: Record<string, string> = {
    standard: 'bg-blue-400',
    vip: 'bg-yellow-400',
    premium: 'bg-purple-400',
    wheelchair: 'bg-green-400',
};

const seatSize = 22;
const seatGap = 3;

export default function SessionBooking({
    event,
    session,
    hall,
    seatStatus,
    ticketTypes,
}: Props) {
    const [selectedSeats, setSelectedSeats] = useState<number[]>([]);
    const [selectedGA, setSelectedGA] = useState<{
        ticketTypeId: number;
        quantity: number;
    } | null>(null);
    const [selectedTT, setSelectedTT] = useState<TicketTypeOption | null>(
        ticketTypes.find((t) => t.mode === 'reserved') ??
            ticketTypes[0] ??
            null,
    );

    const { data, setData, post, processing, errors } = useForm({
        ticket_type_id: selectedTT?.id ?? 0,
        seat_ids: [] as number[],
        quantity: 1,
    });

    const { auth } = usePage().props;
    const isGuest = !auth?.user;

    function requireAuth() {
        const intended = route('events.sessions.book', [event.slug, session.id]);
        router.visit(route('login', { intended }));
    }

    function toggleSeat(seatId: number) {
        setSelectedSeats((prev) =>
            prev.includes(seatId)
                ? prev.filter((id) => id !== seatId)
                : [...prev, seatId],
        );
    }

    function handleHold() {
        if (isGuest) {
            requireAuth();
            return;
        }

        if (selectedTT?.mode === 'reserved') {
            if (selectedSeats.length === 0) return;
            post(route('sessions.hold', session.id), {
                data: {
                    ticket_type_id: selectedTT.id,
                    seat_ids: selectedSeats,
                },
            });
        } else if (selectedGA) {
            post(route('sessions.hold', session.id), {
                data: {
                    ticket_type_id: selectedGA.ticketTypeId,
                    quantity: selectedGA.quantity,
                },
            });
        }
    }

    const reservedTypes = ticketTypes.filter((t) => t.mode === 'reserved');
    const gaTypes = ticketTypes.filter((t) => t.mode === 'general_admission');
    const isReservedMode =
        selectedTT?.mode === 'reserved' || reservedTypes.length > 0;

    const bookedSet = new Set(seatStatus.booked);
    const heldSet = new Set(seatStatus.held);

    return (
        <PublicLayout>
            <Head title={`Book — ${session.title ?? event.title}`} />

            <div className="mx-auto max-w-7xl px-4 py-8">
                <a
                    href={route('events.show', event.slug)}
                    className="text-muted-foreground hover:text-foreground mb-4 flex items-center gap-1 text-sm"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to {event.title}
                </a>

                <div className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight">
                        {session.title ?? event.title}
                    </h1>
                    <p className="text-muted-foreground flex flex-wrap gap-4 text-sm">
                        <span className="flex items-center gap-1">
                            <Calendar className="h-4 w-4" />
                            {new Date(session.start_date).toLocaleDateString()}
                        </span>
                        <span className="flex items-center gap-1">
                            <Clock className="h-4 w-4" />
                            {new Date(
                                session.start_date,
                            ).toLocaleTimeString()}{' '}
                            – {new Date(session.end_date).toLocaleTimeString()}
                        </span>
                        {session.location && (
                            <span className="flex items-center gap-1">
                                <MapPin className="h-4 w-4" />
                                {session.location}
                            </span>
                        )}
                        <span className="flex items-center gap-1">
                            <Users className="h-4 w-4" />
                            {session.available_tickets} available
                        </span>
                    </p>
                </div>

                <div className="mb-6 flex flex-wrap gap-4">
                    {reservedTypes.length > 0 && (
                        <div className="flex items-center gap-2">
                            <label className="text-sm font-medium">
                                Seat Type:
                            </label>
                            <select
                                value={selectedTT?.id ?? ''}
                                onChange={(e) => {
                                    const tt = ticketTypes.find(
                                        (t) => t.id === Number(e.target.value),
                                    );
                                    setSelectedTT(tt ?? null);
                                    setData(
                                        'ticket_type_id',
                                        Number(e.target.value),
                                    );
                                }}
                                className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                            >
                                {reservedTypes.map((tt) => (
                                    <option key={tt.id} value={tt.id}>
                                        {tt.name} — ${tt.price.toFixed(2)}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                    {gaTypes.map((tt) => (
                        <div key={tt.id} className="flex items-center gap-2">
                            <label className="text-sm font-medium">
                                {tt.name} (${tt.price.toFixed(2)}):
                            </label>
                            <input
                                type="number"
                                min={1}
                                max={Math.min(
                                    tt.max_per_order,
                                    tt.quantity_available ?? 50,
                                )}
                                value={
                                    selectedGA?.ticketTypeId === tt.id
                                        ? selectedGA.quantity
                                        : 0
                                }
                                onChange={(e) => {
                                    setSelectedGA({
                                        ticketTypeId: tt.id,
                                        quantity: Number(e.target.value),
                                    });
                                }}
                                className="border-border bg-background w-20 rounded-md border px-2 py-1.5 text-sm"
                            />
                        </div>
                    ))}
                </div>

                {errors.hold && (
                    <div className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">
                        {errors.hold}
                    </div>
                )}

                {isReservedMode && hall && (
                    <div className="mb-8 space-y-6">
                        <h2 className="text-lg font-semibold">
                            Select Your Seats
                        </h2>

                        <div className="text-muted-foreground flex flex-wrap gap-4 text-xs">
                            <span className="flex items-center gap-1">
                                <span className="inline-block h-3 w-3 rounded bg-blue-400" />{' '}
                                Available
                            </span>
                            <span className="flex items-center gap-1">
                                <span className="inline-block h-3 w-3 rounded bg-yellow-400" />{' '}
                                Selected
                            </span>
                            <span className="flex items-center gap-1">
                                <span className="inline-block h-3 w-3 rounded bg-gray-300" />{' '}
                                Held
                            </span>
                            <span className="flex items-center gap-1">
                                <span className="inline-block h-3 w-3 rounded bg-red-400" />{' '}
                                Booked
                            </span>
                        </div>

                        {hall.sections.map((section) => (
                            <div
                                key={section.id}
                                className="border-border bg-card rounded-lg border p-4"
                            >
                                <div className="mb-3 flex items-center gap-2">
                                    <span
                                        className="h-3 w-3 rounded-full"
                                        style={{
                                            backgroundColor:
                                                section.color ?? '#3b82f6',
                                        }}
                                    />
                                    <span className="text-sm font-medium">
                                        {section.name}
                                    </span>
                                    <span className="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-[10px]">
                                        {section.section_type}
                                    </span>
                                </div>

                                <div className="overflow-x-auto">
                                    <div className="flex items-start gap-1">
                                        <div className="flex flex-col gap-[3px] pt-[3px]">
                                            {section.rows.map((row) => (
                                                <div
                                                    key={row.id}
                                                    className="flex items-center justify-end pr-1.5"
                                                    style={{
                                                        height: `${seatSize}px`,
                                                    }}
                                                >
                                                    <span className="text-muted-foreground text-[10px] font-medium">
                                                        {row.label}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                        <div>
                                            {section.rows.map((row) => (
                                                <div
                                                    key={row.id}
                                                    className="flex gap-[3px]"
                                                    style={{
                                                        gap: `${seatGap}px`,
                                                    }}
                                                >
                                                    {row.seats
                                                        .filter(
                                                            (s) => s.is_active,
                                                        )
                                                        .map((seat) => {
                                                            const isBooked =
                                                                bookedSet.has(
                                                                    seat.id,
                                                                );
                                                            const isHeld =
                                                                heldSet.has(
                                                                    seat.id,
                                                                );
                                                            const isSelected =
                                                                selectedSeats.includes(
                                                                    seat.id,
                                                                );
                                                            const isClickable =
                                                                !isBooked &&
                                                                !isHeld;

                                                            return (
                                                                <button
                                                                    key={
                                                                        seat.id
                                                                    }
                                                                    type="button"
                                                                    disabled={
                                                                        !isClickable
                                                                    }
                                                                    onClick={() =>
                                                                        toggleSeat(
                                                                            seat.id,
                                                                        )
                                                                    }
                                                                    title={`${row.label}${seat.seat_number} — ${seat.type}`}
                                                                    className={cn(
                                                                        'rounded-[3px] transition-all',
                                                                        isBooked &&
                                                                            'cursor-not-allowed bg-red-400',
                                                                        isHeld &&
                                                                            'cursor-not-allowed bg-gray-300',
                                                                        isSelected &&
                                                                            'scale-110 bg-yellow-400 ring-2 ring-yellow-600',
                                                                        isClickable &&
                                                                            !isSelected &&
                                                                            (seatTypeColors[
                                                                                seat
                                                                                    .type
                                                                            ] ??
                                                                                'bg-blue-400 hover:scale-110 hover:ring-2 hover:ring-blue-300'),
                                                                    )}
                                                                    style={{
                                                                        width: `${seatSize}px`,
                                                                        height: `${seatSize}px`,
                                                                    }}
                                                                />
                                                            );
                                                        })}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <p className="text-muted-foreground mt-3 text-xs">
                                    {selectedSeats.length} seat(s) selected
                                </p>
                            </div>
                        ))}
                    </div>
                )}

                {isReservedMode && !hall && (
                    <div className="border-border text-muted-foreground mb-8 rounded-lg border-2 border-dashed p-8 text-center text-sm">
                        No seat map is available for this event.
                    </div>
                )}

                <div className="border-border bg-card sticky bottom-4 flex items-center justify-between rounded-lg border p-4 shadow-lg">
                    <div>
                        {isReservedMode ? (
                            <p className="text-sm">
                                <span className="font-medium">
                                    {selectedSeats.length}
                                </span>{' '}
                                seat(s) selected
                            </p>
                        ) : selectedGA ? (
                            <p className="text-sm">
                                <span className="font-medium">
                                    {selectedGA.quantity}
                                </span>{' '}
                                ticket(s)
                            </p>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Select tickets above
                            </p>
                        )}
                    </div>
                    <button
                        onClick={handleHold}
                        disabled={
                            processing ||
                            (isReservedMode
                                ? selectedSeats.length === 0
                                : !selectedGA?.quantity)
                        }
                        className="bg-primary text-primary-foreground hover:bg-primary/90 flex items-center gap-2 rounded-lg px-6 py-2 text-sm font-medium disabled:opacity-50"
                    >
                        <ShoppingCart className="h-4 w-4" />
                        {processing ? 'Holding...' : 'Add to Cart'}
                    </button>
                </div>
            </div>
        </PublicLayout>
    );
}
