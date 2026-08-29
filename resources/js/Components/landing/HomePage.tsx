import { Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import type { PublicEvent } from '@/Pages/Welcome';

const categories = [
    'All',
    'Concerts',
    'Festivals',
    'Conferences',
    'Sports',
    'Comedy',
    'Cultural',
    'Workshops',
    'Business',
    'Networking',
];

function formatShortDate(iso: string | null) {
    if (!iso) return 'TBD';
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function StarRating({ rating }: { rating: number }) {
    return (
        <div className="flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((star) => (
                <svg
                    key={star}
                    viewBox="0 0 24 24"
                    className={`w-3.5 h-3.5 ${
                        star <= Math.round(rating)
                            ? 'fill-warning text-warning'
                            : 'fill-none text-muted'
                    }`}
                    stroke="currentColor"
                    strokeWidth="1.5"
                >
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                </svg>
            ))}
        </div>
    );
}

function EventCard({
    event,
    size = 'normal',
}: {
    event: PublicEvent;
    size?: 'normal' | 'large';
}) {
    return (
        <Link
            href={`/events/${event.slug}`}
            className={`card-hover group w-full text-left rounded-2xl overflow-hidden border border-rim bg-surface flex flex-col ${size === 'large' ? 'h-96' : ''}`}
        >
            <div
                className={`relative overflow-hidden bg-ghost ${size === 'large' ? 'h-56' : 'aspect-video'}`}
            >
                <img
                    src={
                        event.cover_url ||
                        'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=1200&h=700&fit=crop&auto=format'
                    }
                    alt={event.title}
                    loading="lazy"
                    className="img-hover w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                <div className="absolute top-3 left-3">
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-black/50 text-white border border-white/20 backdrop-blur-sm">
                        {event.category || 'Event'}
                    </span>
                </div>
                <div className="absolute bottom-3 left-3 right-3 flex items-end justify-between">
                    <div className="flex items-center gap-1.5 text-white/80 text-xs">
                        <svg
                            viewBox="0 0 24 24"
                            className="w-3.5 h-3.5"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        {event.organization_name || ''}
                    </div>
                    <div className="text-white font-semibold text-sm">
                        {event.next_session_date
                            ? formatShortDate(event.next_session_date)
                            : ''}
                    </div>
                </div>
            </div>
            <div className="p-4 flex flex-col gap-2 flex-1">
                <h3 className="font-display font-semibold text-white text-base leading-snug line-clamp-2 group-hover:text-purple-glow transition-colors">
                    {event.title}
                </h3>
                <div className="flex items-center gap-1.5 text-muted text-xs">
                    <svg
                        viewBox="0 0 24 24"
                        className="w-3.5 h-3.5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                    >
                        <rect
                            x="3"
                            y="4"
                            width="18"
                            height="18"
                            rx="2"
                            ry="2"
                        />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    {event.next_session_date
                        ? formatShortDate(event.next_session_date)
                        : 'TBD'}
                </div>
                <div className="flex items-center gap-1.5 text-muted text-xs">
                    <svg
                        viewBox="0 0 24 24"
                        className="w-3.5 h-3.5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                    >
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <span className="truncate">
                        {event.organization_name || 'Unknown Organizer'}
                    </span>
                </div>
            </div>
        </Link>
    );
}

function HeroEvent({ event }: { event: PublicEvent }) {
    return (
        <div className="relative w-full h-[90vh] min-h-[600px] overflow-hidden">
            <img
                src={
                    event.cover_url ||
                    'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=1200&h=700&fit=crop&auto=format'
                }
                alt={event.title}
                className="absolute inset-0 w-full h-full object-cover"
            />
            <div className="hero-overlay absolute inset-0" />
            <div className="absolute inset-0 flex flex-col justify-end px-6 sm:px-12 pb-16 max-w-5xl">
                <div className="flex items-center gap-3 mb-5">
                    <span className="px-3 py-1 rounded-full text-xs font-semibold gradient-btn text-white">
                        ✦ Featured Event
                    </span>
                    <span className="px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-white border border-white/20 backdrop-blur-sm">
                        {event.category || 'Event'}
                    </span>
                </div>
                <h1 className="font-display text-5xl sm:text-7xl font-bold text-white leading-none tracking-tight mb-4">
                    {event.title}
                </h1>
                <p className="text-white/70 text-lg max-w-lg leading-relaxed mb-3">
                    {event.description || ''}
                </p>
                <div className="flex flex-wrap items-center gap-4 text-white/60 text-sm mb-8">
                    <span className="flex items-center gap-1.5">
                        <svg
                            viewBox="0 0 24 24"
                            className="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            <rect
                                x="3"
                                y="4"
                                width="18"
                                height="18"
                                rx="2"
                            />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        {event.next_session_date
                            ? formatShortDate(event.next_session_date)
                            : 'TBD'}
                    </span>
                    <span className="flex items-center gap-1.5">
                        <svg
                            viewBox="0 0 24 24"
                            className="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        {event.organization_name || 'Unknown Organizer'}
                    </span>
                </div>
                <div className="flex items-center gap-4">
                    <Link
                        href={`/events/${event.slug}`}
                        className="gradient-btn px-8 py-3.5 rounded-xl font-semibold text-white text-sm tracking-wide"
                    >
                        Get Tickets
                    </Link>
                    <Link
                        href={`/events/${event.slug}`}
                        className="px-8 py-3.5 rounded-xl font-semibold text-white text-sm border border-white/20 bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-colors"
                    >
                        View Details
                    </Link>
                </div>
            </div>
        </div>
    );
}

interface HomePageProps {
    featuredEvents: PublicEvent[];
    trendingEvents: PublicEvent[];
}

export default function HomePage({
    featuredEvents,
    trendingEvents,
}: HomePageProps) {
    const [activeCategory, setActiveCategory] = useState('All');
    const [search, setSearch] = useState('');

    const allEvents = useMemo(() => {
        const combined = [...featuredEvents, ...trendingEvents];
        const seen = new Set<number>();
        return combined.filter((e) => {
            if (seen.has(e.id)) return false;
            seen.add(e.id);
            return true;
        });
    }, [featuredEvents, trendingEvents]);

    const filteredEvents = useMemo(() => {
        return allEvents.filter((e) => {
            const matchCat =
                activeCategory === 'All' ||
                e.category?.toLowerCase() === activeCategory.toLowerCase();
            const matchSearch =
                search.trim() === '' ||
                e.title.toLowerCase().includes(search.toLowerCase()) ||
                (e.organization_name || '')
                    .toLowerCase()
                    .includes(search.toLowerCase());
            return matchCat && matchSearch;
        });
    }, [allEvents, activeCategory, search]);

    const heroEvent = featuredEvents[0];

    if (!heroEvent) return null;

    return (
        <div className="min-h-screen">
            {/* Hero */}
            <HeroEvent event={heroEvent} />

            {/* Search + Categories */}
            <div className="sticky top-16 z-40 bg-void/90 backdrop-blur-md border-b border-rim">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row gap-3 items-center">
                    {/* Search */}
                    <div className="relative flex-1 max-w-sm w-full">
                        <svg
                            viewBox="0 0 24 24"
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="M21 21l-4.35-4.35" />
                        </svg>
                        <input
                            type="text"
                            placeholder="Search events, artists, venues…"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full bg-surface border border-rim rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder:text-muted focus:outline-none focus:border-purple/60 transition-colors"
                        />
                    </div>

                    {/* Category pills */}
                    <div className="flex items-center gap-2 overflow-x-auto py-0.5 shrink-0">
                        {categories.map((cat) => (
                            <button
                                key={cat}
                                onClick={() => setActiveCategory(cat)}
                                className={`shrink-0 px-4 py-2 rounded-full text-xs font-medium transition-all ${
                                    activeCategory === cat
                                        ? 'gradient-btn text-white shadow-lg'
                                        : 'bg-surface border border-rim text-muted hover:text-white hover:border-white/20'
                                }`}
                            >
                                {cat}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-12 space-y-16">
                {/* Search results banner */}
                {search.trim() !== '' && (
                    <div className="text-muted text-sm">
                        {filteredEvents.length} result
                        {filteredEvents.length !== 1 ? 's' : ''} for{' '}
                        <span className="text-white font-medium">
                            &ldquo;{search}&rdquo;
                        </span>
                        <button
                            onClick={() => setSearch('')}
                            className="ml-3 text-purple hover:text-purple-glow"
                        >
                            Clear
                        </button>
                    </div>
                )}

                {/* Featured Events + Trending + All Events when no filter */}
                {activeCategory === 'All' && search.trim() === '' ? (
                    <>
                        <section>
                            <div className="flex items-end justify-between mb-6">
                                <div>
                                    <p className="text-purple text-xs font-semibold uppercase tracking-widest mb-1">
                                        Handpicked
                                    </p>
                                    <h2 className="font-display text-3xl font-semibold text-white">
                                        Featured Events
                                    </h2>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                {featuredEvents.map((event) => (
                                    <EventCard
                                        key={event.id}
                                        event={event}
                                    />
                                ))}
                            </div>
                        </section>

                        {/* Trending */}
                        {trendingEvents.length > 0 && (
                            <section>
                                <div className="flex items-end justify-between mb-6">
                                    <div>
                                        <p className="text-pink text-xs font-semibold uppercase tracking-widest mb-1">
                                            Trending Now
                                        </p>
                                        <h2 className="font-display text-3xl font-semibold text-white">
                                            Hot This Week
                                        </h2>
                                    </div>
                                </div>
                                <div className="flex gap-5 overflow-x-auto pb-4">
                                    {trendingEvents.map((event) => (
                                        <div
                                            key={event.id}
                                            className="shrink-0 w-72"
                                        >
                                            <EventCard event={event} />
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {/* All upcoming events */}
                        <section>
                            <div className="mb-6">
                                <p className="text-muted text-xs font-semibold uppercase tracking-widest mb-1">
                                    Up Next
                                </p>
                                <h2 className="font-display text-3xl font-semibold text-white">
                                    All Events
                                </h2>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                                {allEvents.map((event) => (
                                    <EventCard
                                        key={event.id}
                                        event={event}
                                    />
                                ))}
                            </div>
                        </section>
                    </>
                ) : (
                    <section>
                        <div className="mb-6">
                            <h2 className="font-display text-3xl font-semibold text-white">
                                {activeCategory === 'All'
                                    ? 'All Events'
                                    : activeCategory}
                            </h2>
                            <p className="text-muted text-sm mt-1">
                                {filteredEvents.length} events
                            </p>
                        </div>
                        {filteredEvents.length === 0 ? (
                            <div className="text-center py-24">
                                <h3 className="font-display text-xl text-white mb-2">
                                    No events found
                                </h3>
                                <p className="text-muted text-sm">
                                    Try a different search or browse all
                                    categories
                                </p>
                                <button
                                    onClick={() => {
                                        setSearch('');
                                        setActiveCategory('All');
                                    }}
                                    className="mt-6 gradient-btn px-6 py-2.5 rounded-xl text-white text-sm font-medium"
                                >
                                    Browse All Events
                                </button>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                                {filteredEvents.map((event) => (
                                    <EventCard
                                        key={event.id}
                                        event={event}
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                )}
            </div>
        </div>
    );
}
