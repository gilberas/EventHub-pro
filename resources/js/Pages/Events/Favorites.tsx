import PublicLayout from '@/Layouts/PublicLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Bookmark, Calendar, MapPin } from 'lucide-react';

interface FavoriteEvent {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    category: string | null;
    cover_url: string | null;
    organization_name: string | null;
    next_session_date: string | null;
    sessions:
        | {
              id: number;
              start_date: string;
              location: string | null;
              ticket_types:
                  | { id: number; price: number; name: string }[]
                  | null;
          }[]
        | null;
}

export default function Favorites({ events }: { events: FavoriteEvent[] }) {
    function minPrice(
        event: FavoriteEvent,
    ): number | null {
        const prices = (
            event.sessions?.flatMap((s) => s.ticket_types ?? []) ?? []
        ).map((t) => t.price);

        return prices.length > 0 ? Math.min(...prices) : null;
    }

    function removeFavorite(event: FavoriteEvent) {
        router.delete(route('events.unfavorite', event.id), {
            preserveScroll: true,
        });
    }

    return (
        <PublicLayout>
            <Head title="My Favorites" />

            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-2 text-2xl font-bold tracking-tight">
                    My Favorites
                </h1>
                <p className="text-muted-foreground mb-8">
                    Events you've saved for later.
                </p>

                {events.length === 0 ? (
                    <div className="border-border text-muted-foreground rounded-lg border-2 border-dashed p-12 text-center">
                        <Bookmark className="mx-auto mb-2 h-8 w-8" />
                        <p>You haven't saved any events yet.</p>
                        <Link
                            href="/events/search"
                            className="text-primary mt-2 inline-block text-sm underline"
                        >
                            Browse events
                        </Link>
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {events.map((event) => (
                            <div
                                key={event.id}
                                className="border-border bg-card group overflow-hidden rounded-lg border"
                            >
                                <Link href={route('events.show', event.slug)}>
                                    {event.cover_url ? (
                                        <img
                                            src={event.cover_url}
                                            alt={event.title}
                                            className="h-44 w-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="bg-muted flex h-44 w-full items-center justify-center text-3xl font-bold">
                                            {event.title.charAt(0)}
                                        </div>
                                    )}
                                </Link>
                                <div className="p-4">
                                    <Link
                                        href={route('events.show', event.slug)}
                                        className="font-semibold hover:underline"
                                    >
                                        {event.title}
                                    </Link>
                                    <p className="text-muted-foreground text-sm">
                                        {event.organization_name}
                                    </p>
                                    {event.category && (
                                        <span className="bg-primary/10 text-primary mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium">
                                            {event.category}
                                        </span>
                                    )}
                                    {event.next_session_date && (
                                        <p className="text-muted-foreground mt-2 flex items-center gap-1 text-sm">
                                            <Calendar className="h-3.5 w-3.5" />
                                            {new Date(
                                                event.next_session_date,
                                            ).toLocaleDateString()}
                                        </p>
                                    )}
                                    {event.sessions?.[0]?.location && (
                                        <p className="text-muted-foreground flex items-center gap-1 text-sm">
                                            <MapPin className="h-3.5 w-3.5" />
                                            {event.sessions[0].location}
                                        </p>
                                    )}
                                    <div className="mt-3 flex items-center justify-between border-t pt-3">
                                        <span className="font-semibold">
                                            {minPrice(event) !== null
                                                ? `From $${minPrice(event)!.toFixed(2)}`
                                                : 'Free'}
                                        </span>
                                        <button
                                            onClick={() =>
                                                removeFavorite(event)
                                            }
                                            className="text-muted-foreground hover:text-primary flex items-center gap-1 text-xs"
                                        >
                                            <Bookmark className="h-3.5 w-3.5" />
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}