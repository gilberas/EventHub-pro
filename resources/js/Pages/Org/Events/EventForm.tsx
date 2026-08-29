import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { VenueOption } from './Create';

type TicketMode = 'general_admission' | 'reserved';

interface TicketTypeInput {
    name: string;
    price: string;
    description: string;
    mode: TicketMode;
    quantity_available: string;
    max_per_order: string;
}

interface EventSessionInput {
    title: string;
    start_date: string;
    end_date: string;
    location: string;
    capacity: string;
    venue_id: string;
    ticket_types: TicketTypeInput[];
}

interface EventFormProps {
    isEdit: boolean;
    venues: VenueOption[];
    initial?: {
        id: number;
        title: string;
        description: string | null;
        category: string | null;
        tags: string[] | null;
        status: string;
        is_featured: boolean;
        age_restriction: number | null;
        refund_policy_days: number | null;
        refund_policy_percentage: number | null;
        cover_url: string | null;
        sessions:
            | {
                  title: string | null;
                  start_date: string;
                  end_date: string;
                  venue_id: number | null;
                  location: string | null;
                  capacity: number | null;
                  ticket_types:
                      | {
                            name: string;
                            price: string;
                            description?: string | null;
                            mode: string;
                            quantity_available?: number | null;
                            max_per_order?: number | null;
                        }[]
                      | null;
              }[]
            | null;
    };
}

const emptyTicketType = (): TicketTypeInput => ({
    name: '',
    price: '',
    description: '',
    mode: 'general_admission',
    quantity_available: '',
    max_per_order: '',
});

const emptySession = (): EventSessionInput => ({
    title: '',
    start_date: '',
    end_date: '',
    location: '',
    capacity: '',
    venue_id: '',
    ticket_types: [emptyTicketType()],
});

export default function EventForm({ isEdit, venues, initial }: EventFormProps) {
    const [sessions, setSessions] = useState<EventSessionInput[]>(
        () =>
            initial?.sessions?.map((s) => ({
                title: s.title ?? '',
                start_date: new Date(s.start_date).toISOString().slice(0, 16),
                end_date: new Date(s.end_date).toISOString().slice(0, 16),
                location: s.location ?? '',
                capacity: s.capacity !== null ? String(s.capacity) : '',
                venue_id: s.venue_id !== null ? String(s.venue_id) : '',
                ticket_types:
                    s.ticket_types?.map((t) => ({
                        name: t.name,
                        price: String(t.price),
                        description: t.description ?? '',
                        mode: (t.mode === 'reserved'
                            ? 'reserved'
                            : 'general_admission') as TicketMode,
                        quantity_available:
                            t.quantity_available !== null &&
                            t.quantity_available !== undefined
                                ? String(t.quantity_available)
                                : '',
                        max_per_order:
                            t.max_per_order !== null &&
                            t.max_per_order !== undefined
                                ? String(t.max_per_order)
                                : '',
                    })) ?? [emptyTicketType()],
            })) ?? [emptySession()],
    );

    const { data, setData, post, put, processing, errors, reset } = useForm({
        title: initial?.title ?? '',
        description: initial?.description ?? '',
        category: initial?.category ?? '',
        tags: (initial?.tags ?? []).join(', '),
        status: initial?.status ?? 'draft',
        is_featured: initial?.is_featured ?? false,
        age_restriction: initial?.age_restriction?.toString() ?? '',
        refund_policy_days: initial?.refund_policy_days?.toString() ?? '',
        refund_policy_percentage:
            initial?.refund_policy_percentage?.toString() ?? '',
        cover: null as File | null,
    });

    function buildPayload() {
        const safeNumber = (value: string): number | null =>
            value === '' ? null : Number(value);

        return {
            title: data.title,
            description: data.description === '' ? null : data.description,
            category: data.category === '' ? null : data.category,
            tags: data.tags
                .split(',')
                .map((tag) => tag.trim())
                .filter(Boolean),
            status: data.status,
            is_featured: data.is_featured,
            age_restriction: safeNumber(data.age_restriction),
            refund_policy_days: safeNumber(data.refund_policy_days),
            refund_policy_percentage: safeNumber(data.refund_policy_percentage),
        };
    }

    function buildSessionsPayload() {
        return sessions
            .filter((s) => s.start_date && s.end_date)
            .map((s) => ({
                title: s.title || null,
                start_date: s.start_date,
                end_date: s.end_date,
                location: s.location || null,
                capacity: s.capacity === '' ? null : Number(s.capacity),
                venue_id: s.venue_id === '' ? null : Number(s.venue_id),
                ticket_types: s.ticket_types
                    .filter((t) => t.name.trim() !== '')
                    .map((t) => ({
                        name: t.name,
                        price: t.price === '' ? '0' : t.price,
                        description: t.description === '' ? null : t.description,
                        mode: t.mode,
                        quantity_available:
                            t.quantity_available === ''
                                ? null
                                : Number(t.quantity_available),
                        max_per_order:
                            t.max_per_order === ''
                                ? null
                                : Number(t.max_per_order),
                    })),
            }));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        const payload = buildPayload();

        if (isEdit && initial) {
            put(route('events.update', initial.id), {
                data: { ...payload, cover: data.cover ?? undefined },
            });

            return;
        }

        post(route('events.store'), {
            data: {
                ...payload,
                cover: data.cover ?? undefined,
                sessions: buildSessionsPayload(),
            },
            onSuccess: () => {
                reset('cover');
            },
        });
    }

    function updateSession<K extends keyof EventSessionInput>(
        index: number,
        field: K,
        value: EventSessionInput[K],
    ) {
        setSessions((prev) =>
            prev.map((s, i) => (i === index ? { ...s, [field]: value } : s)),
        );
    }

    function updateTicketType(
        sessionIndex: number,
        ticketIndex: number,
        field: keyof TicketTypeInput,
        value: string,
    ) {
        setSessions((prev) =>
            prev.map((s, i) =>
                i === sessionIndex
                    ? {
                          ...s,
                          ticket_types: s.ticket_types.map((t, j) =>
                              j === ticketIndex
                                  ? {
                                        ...t,
                                        [field]:
                                            field === 'mode'
                                                ? (value as TicketMode)
                                                : value,
                                    }
                                  : t,
                          ),
                      }
                    : s,
            ),
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <WidgetCard title="Event Details">
                <div className="space-y-4">
                    <div>
                        <label className="text-sm font-medium">Title</label>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="e.g. Summer Music Festival 2026"
                            className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                        />
                        {errors.title && (
                            <p className="mt-1 text-xs text-red-500">
                                {errors.title}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="text-sm font-medium">
                            Description
                        </label>
                        <textarea
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={4}
                            className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium">
                                Category
                            </label>
                            <input
                                type="text"
                                value={data.category}
                                onChange={(e) =>
                                    setData('category', e.target.value)
                                }
                                placeholder="e.g. Music, Technology, Food"
                                className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium">
                                Tags (comma separated)
                            </label>
                            <input
                                type="text"
                                value={data.tags}
                                onChange={(e) =>
                                    setData('tags', e.target.value)
                                }
                                placeholder="festival, summer, live"
                                className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium">
                                Status
                            </label>
                            <select
                                value={data.status}
                                onChange={(e) =>
                                    setData('status', e.target.value)
                                }
                                className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium">
                                Age Restriction
                            </label>
                            <input
                                type="number"
                                min={0}
                                max={255}
                                value={data.age_restriction}
                                onChange={(e) =>
                                    setData('age_restriction', e.target.value)
                                }
                                placeholder="18"
                                className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium">
                                Refund Policy (days)
                            </label>
                            <input
                                type="number"
                                min={0}
                                max={365}
                                value={data.refund_policy_days}
                                onChange={(e) =>
                                    setData(
                                        'refund_policy_days',
                                        e.target.value,
                                    )
                                }
                                placeholder="14"
                                className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium">
                                Refund Percentage
                            </label>
                            <input
                                type="number"
                                min={0}
                                max={100}
                                step="0.01"
                                value={data.refund_policy_percentage}
                                onChange={(e) =>
                                    setData(
                                        'refund_policy_percentage',
                                        e.target.value,
                                    )
                                }
                                placeholder="100"
                                className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.is_featured}
                            onChange={(e) =>
                                setData('is_featured', e.target.checked)
                            }
                            className="border-border rounded"
                        />
                        Feature this event on the homepage
                    </label>
                </div>
            </WidgetCard>

            <WidgetCard title="Cover Image">
                <p className="text-muted-foreground mb-3 text-sm">
                    A high-quality cover makes the event stand out on featured
                    and trending sections.
                </p>
                {isEdit && initial?.cover_url && (
                    <img
                        src={initial.cover_url}
                        alt="Current cover"
                        className="mb-3 h-40 w-full rounded-md object-cover"
                    />
                )}
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    onChange={(e) =>
                        setData('cover', e.target.files?.[0] ?? null)
                    }
                    className="text-muted-foreground file:bg-primary/10 file:text-primary block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:px-3 file:py-2 file:text-sm file:font-medium"
                />
                {errors.cover && (
                    <p className="mt-1 text-xs text-red-500">{errors.cover}</p>
                )}
            </WidgetCard>

            {!isEdit && (
                <WidgetCard title="Sessions">
                    <p className="text-muted-foreground mb-4 text-sm">
                        Define the event's date, time, capacity, and ticket
                        types. Choose a venue for reserved seating.
                    </p>
                    <div className="space-y-4">
                        {sessions.map((session, index) => (
                            <div
                                key={index}
                                className="border-border rounded-lg border p-4"
                            >
                                <div className="mb-3 flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        Session {index + 1}
                                    </span>
                                    {sessions.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setSessions((prev) =>
                                                    prev.filter(
                                                        (_, i) => i !== index,
                                                    ),
                                                )
                                            }
                                            className="flex items-center gap-1 text-xs text-red-500 hover:text-red-700"
                                        >
                                            <Trash2 className="h-3 w-3" />
                                            Remove
                                        </button>
                                    )}
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label className="text-muted-foreground text-xs">
                                            Title
                                        </label>
                                        <input
                                            type="text"
                                            value={session.title}
                                            onChange={(e) =>
                                                updateSession(
                                                    index,
                                                    'title',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Main Session"
                                            className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-muted-foreground text-xs">
                                            Location
                                        </label>
                                        <input
                                            type="text"
                                            value={session.location}
                                            onChange={(e) =>
                                                updateSession(
                                                    index,
                                                    'location',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="City or venue"
                                            className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-muted-foreground text-xs">
                                            Start
                                        </label>
                                        <input
                                            type="datetime-local"
                                            value={session.start_date}
                                            onChange={(e) =>
                                                updateSession(
                                                    index,
                                                    'start_date',
                                                    e.target.value,
                                                )
                                            }
                                            className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-muted-foreground text-xs">
                                            End
                                        </label>
                                        <input
                                            type="datetime-local"
                                            value={session.end_date}
                                            onChange={(e) =>
                                                updateSession(
                                                    index,
                                                    'end_date',
                                                    e.target.value,
                                                )
                                            }
                                            className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-muted-foreground text-xs">
                                            Capacity
                                        </label>
                                        <input
                                            type="number"
                                            min={1}
                                            value={session.capacity}
                                            onChange={(e) =>
                                                updateSession(
                                                    index,
                                                    'capacity',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="500"
                                            className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-muted-foreground text-xs">
                                            Venue (for reserved seating)
                                        </label>
                                        <select
                                            value={session.venue_id}
                                            onChange={(e) =>
                                                updateSession(
                                                    index,
                                                    'venue_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                        >
                                            <option value="">No venue</option>
                                            {venues.map((v) => (
                                                <option
                                                    key={v.id}
                                                    value={String(v.id)}
                                                >
                                                    {v.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="border-border mt-4 rounded-md border p-3">
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="text-xs font-medium">
                                            Ticket Types
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                updateSession(index, 'ticket_types', [
                                                    ...session.ticket_types,
                                                    emptyTicketType(),
                                                ])
                                            }
                                            className="text-primary flex items-center gap-1 text-xs hover:underline"
                                        >
                                            <Plus className="h-3 w-3" />
                                            Add Ticket Type
                                        </button>
                                    </div>
                                    {session.ticket_types.map(
                                        (ticket, ticketIndex) => (
                                            <div
                                                key={ticketIndex}
                                                className="border-border mb-3 grid gap-2 rounded border bg-background/50 p-2 sm:grid-cols-6"
                                            >
                                                <div className="sm:col-span-2">
                                                    <label className="text-muted-foreground text-xs">
                                                        Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={ticket.name}
                                                        onChange={(e) =>
                                                            updateTicketType(
                                                                index,
                                                                ticketIndex,
                                                                'name',
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="General"
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Price
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min={0}
                                                        step="0.01"
                                                        value={ticket.price}
                                                        onChange={(e) =>
                                                            updateTicketType(
                                                                index,
                                                                ticketIndex,
                                                                'price',
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="25.00"
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Type
                                                    </label>
                                                    <select
                                                        value={ticket.mode}
                                                        onChange={(e) =>
                                                            updateTicketType(
                                                                index,
                                                                ticketIndex,
                                                                'mode',
                                                                e.target.value as TicketMode,
                                                            )
                                                        }
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    >
                                                        <option value="general_admission">
                                                            General
                                                        </option>
                                                        <option value="reserved">
                                                            Reserved seating
                                                        </option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Available
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min={0}
                                                        value={
                                                            ticket.quantity_available
                                                        }
                                                        onChange={(e) =>
                                                            updateTicketType(
                                                                index,
                                                                ticketIndex,
                                                                'quantity_available',
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="Unlimited"
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="text-muted-foreground text-xs">
                                                        Max / Order
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min={1}
                                                        value={
                                                            ticket.max_per_order
                                                        }
                                                        onChange={(e) =>
                                                            updateTicketType(
                                                                index,
                                                                ticketIndex,
                                                                'max_per_order',
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="5"
                                                        className="border-border bg-background mt-0.5 w-full rounded-md border px-2 py-1 text-xs"
                                                    />
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </div>
                        ))}
                        <button
                            type="button"
                            onClick={() =>
                                setSessions((prev) => [
                                    ...prev,
                                    emptySession(),
                                ])
                            }
                            className="text-primary flex items-center gap-1 text-sm hover:underline"
                        >
                            <Plus className="h-4 w-4" />
                            Add Session
                        </button>
                    </div>
                </WidgetCard>
            )}

            <div className="flex gap-2">
                <button
                    type="submit"
                    disabled={processing || !data.title.trim()}
                    className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                >
                    {processing
                        ? isEdit
                            ? 'Saving...'
                            : 'Creating...'
                        : isEdit
                          ? 'Save Changes'
                          : 'Create Event'}
                </button>
            </div>
        </form>
    );
}