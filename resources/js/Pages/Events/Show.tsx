import PublicLayout from '@/Layouts/PublicLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Bookmark, Calendar, Clock, MapPin, Ticket, Users } from 'lucide-react';
import { useState } from 'react';

interface TicketType {
    id: number;
    name: string;
    mode: string;
    price: number;
    quantity_available: number | null;
    max_per_order: number;
    sort_order: number;
}

interface Session {
    id: number;
    title: string | null;
    start_date: string;
    end_date: string;
    location: string | null;
    capacity: number | null;
    available_tickets: number;
    ticket_types: TicketType[] | null;
}

interface PublicEvent {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    category: string | null;
    tags: string[] | null;
    cover_url: string | null;
    gallery_urls: string[];
    organization_id: number;
    organization_name: string | null;
    age_restriction: number | null;
    terms: string | null;
    sessions: Session[] | null;
}

interface Props {
    event: PublicEvent;
    is_favorited?: boolean;
}

export default function EventShow({ event, is_favorited = false }: Props) {
    const { auth } = usePage().props;
    const user = auth?.user ?? null;
    const [selectedSession, setSelectedSession] = useState<Session | null>(
        null,
    );
    const [favorited, setFavorited] = useState(is_favorited);
    const [favoriting, setFavoriting] = useState(false);

    function handleBook(session: Session) {
        const bookUrl = route('events.sessions.book', [event.slug, session.id]);

        if (!user) {
            router.visit(route('login', { intended: bookUrl }));
            return;
        }
        router.visit(bookUrl);
    }

    function handleToggleFavorite() {
        if (!user) {
            router.visit(route('login', { intended: window.location.pathname }));
            return;
        }

        setFavoriting(true);

        if (favorited) {
            router.delete(route('events.unfavorite', event.id), {
                preserveScroll: true,
                onSuccess: () => setFavorited(false),
                onFinish: () => setFavoriting(false),
            });
        } else {
            router.post(
                route('events.favorite', event.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => setFavorited(true),
                    onFinish: () => setFavoriting(false),
                },
            );
        }
    }

    return (
        <PublicLayout>
            <Head title={event.title} />

            <div className="mx-auto max-w-7xl px-4 py-8">
                {event.cover_url && (
                    <img
                        src={event.cover_url}
                        alt={event.title}
                        className="mb-6 h-64 w-full rounded-xl object-cover"
                    />
                )}

                <div className="mb-8">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-4xl font-bold tracking-tight">
                                {event.title}
                            </h1>
                            <p className="text-muted-foreground mt-2">
                                {event.organization_name}
                            </p>
                        </div>
                        <button
                            onClick={handleToggleFavorite}
                            disabled={favoriting}
                            title={
                                favorited
                                    ? 'Remove from favorites'
                                    : 'Save to favorites'
                            }
                            className={`flex items-center gap-1 rounded-lg border px-3 py-2 text-sm font-medium transition-colors disabled:opacity-50 ${
                                favorited
                                    ? 'border-primary/30 bg-primary/10 text-primary'
                                    : 'border-border text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Bookmark
                                className={`h-4 w-4 ${favorited ? 'fill-current' : ''}`}
                            />
                            {favorited ? 'Saved' : 'Save'}
                        </button>
                    </div>
                    {event.category && (
                        <span className="bg-primary/10 text-primary mt-2 inline-block rounded-full px-3 py-1 text-xs font-medium">
                            {event.category}
                        </span>
                    )}
                    {event.age_restriction && (
                        <span className="ml-2 inline-block rounded-full bg-yellow-500/10 px-3 py-1 text-xs font-medium text-yellow-600">
                            18+
                        </span>
                    )}
                </div>

                {event.description && (
                    <div className="prose prose-sm text-muted-foreground mb-8 max-w-none">
                        {event.description.split('\n').map((p, i) => (
                            <p key={i}>{p}</p>
                        ))}
                    </div>
                )}

                {event.sessions && event.sessions.length > 0 && (
                    <div className="mb-8">
                        <h2 className="mb-4 text-xl font-semibold">
                            Available Sessions
                        </h2>
                        <div className="space-y-4">
                            {event.sessions.map((session) => {
                                const isSoldOut =
                                    session.available_tickets <= 0;
                                const minPrice = session.ticket_types?.length
                                    ? Math.min(
                                          ...session.ticket_types.map(
                                              (t) => t.price,
                                          ),
                                      )
                                    : null;

                                return (
                                    <div
                                        key={session.id}
                                        className={`rounded-lg border p-4 transition-colors ${isSoldOut ? 'border-red-200 bg-red-50/50' : 'border-border hover:border-primary'}`}
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div className="space-y-2">
                                                <h3 className="font-semibold">
                                                    {session.title ??
                                                        'Main Session'}
                                                </h3>
                                                <div className="text-muted-foreground flex flex-wrap gap-4 text-sm">
                                                    <span className="flex items-center gap-1">
                                                        <Calendar className="h-4 w-4" />
                                                        {new Date(
                                                            session.start_date,
                                                        ).toLocaleDateString(
                                                            'en-US',
                                                            {
                                                                weekday: 'long',
                                                                month: 'long',
                                                                day: 'numeric',
                                                                year: 'numeric',
                                                            },
                                                        )}
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Clock className="h-4 w-4" />
                                                        {new Date(
                                                            session.start_date,
                                                        ).toLocaleTimeString(
                                                            'en-US',
                                                            {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            },
                                                        )}
                                                        {' – '}
                                                        {new Date(
                                                            session.end_date,
                                                        ).toLocaleTimeString(
                                                            'en-US',
                                                            {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            },
                                                        )}
                                                    </span>
                                                    {session.location && (
                                                        <span className="flex items-center gap-1">
                                                            <MapPin className="h-4 w-4" />
                                                            {session.location}
                                                        </span>
                                                    )}
                                                </div>
                                                {session.capacity && (
                                                    <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                                        <Users className="h-3 w-3" />
                                                        {
                                                            session.available_tickets
                                                        }{' '}
                                                        of {session.capacity}{' '}
                                                        available
                                                    </div>
                                                )}
                                                {session.ticket_types &&
                                                    session.ticket_types
                                                        .length > 0 && (
                                                        <div className="flex flex-wrap gap-2 pt-1">
                                                            {session.ticket_types.map(
                                                                (tt) => (
                                                                    <span
                                                                        key={
                                                                            tt.id
                                                                        }
                                                                        className="rounded-full bg-green-500/10 px-2 py-0.5 text-xs font-medium text-green-700"
                                                                    >
                                                                        {
                                                                            tt.name
                                                                        }{' '}
                                                                        — $
                                                                        {tt.price.toFixed(
                                                                            2,
                                                                        )}
                                                                    </span>
                                                                ),
                                                            )}
                                                        </div>
                                                    )}
                                            </div>
                                            <div className="flex flex-col items-end gap-2">
                                                {minPrice !== null && (
                                                    <span className="text-lg font-bold">
                                                        From $
                                                        {minPrice.toFixed(2)}
                                                    </span>
                                                )}
                                                <button
                                                    onClick={() =>
                                                        handleBook(session)
                                                    }
                                                    disabled={isSoldOut}
                                                    className={`flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium ${
                                                        isSoldOut
                                                            ? 'cursor-not-allowed bg-red-100 text-red-500'
                                                            : 'bg-primary text-primary-foreground hover:bg-primary/90'
                                                    }`}
                                                >
                                                    <Ticket className="h-4 w-4" />
                                                    {isSoldOut
                                                        ? 'Sold Out'
                                                        : 'Get Tickets'}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {event.terms && (
                    <div className="bg-muted/50 text-muted-foreground rounded-lg p-4 text-xs">
                        <h4 className="mb-1 font-medium">Terms & Conditions</h4>
                        <p>{event.terms}</p>
                    </div>
                )}

                {event.gallery_urls && event.gallery_urls.length > 0 && (
                    <div className="mt-8">
                        <h2 className="mb-4 text-xl font-semibold">Gallery</h2>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            {event.gallery_urls.map((url, i) => (
                                <img
                                    key={i}
                                    src={url}
                                    alt={`Gallery ${i + 1}`}
                                    className="h-40 w-full rounded-lg object-cover"
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
