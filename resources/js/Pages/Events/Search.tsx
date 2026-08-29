import PublicLayout from '@/Layouts/PublicLayout';
import { Head, router } from '@inertiajs/react';
import { Calendar, ChevronLeft, ChevronRight, Filter, Search, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface PublicEvent {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    category: string | null;
    cover_url: string | null;
    organization_name: string | null;
    next_session_date: string | null;
}

interface Props {
    events: PublicEvent[];
    filters: {
        q?: string;
        category?: string;
        date_from?: string;
        date_to?: string;
        location?: string;
        price_min?: string;
        price_max?: string;
        sort?: string;
    };
    pagination?: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
}

export default function SearchResultPage({ events, filters, pagination }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');
    const [location, setLocation] = useState(filters.location ?? '');
    const [priceMin, setPriceMin] = useState(filters.price_min ?? '');
    const [priceMax, setPriceMax] = useState(filters.price_max ?? '');
    const [sort, setSort] = useState(filters.sort ?? '');
    const [showFilters, setShowFilters] = useState(false);

    function handleSearch(e?: FormEvent, page = 1) {
        e?.preventDefault();
        router.get(
            route('events.search'),
            {
                q,
                category,
                date_from: dateFrom,
                date_to: dateTo,
                location,
                price_min: priceMin,
                price_max: priceMax,
                sort,
                page,
            },
            { preserveState: true },
        );
    }

    function goToPage(page: number) {
        if (page < 1 || (pagination && page > pagination.last_page)) {
            return;
        }
        handleSearch(undefined, page);
    }

    function clearFilters() {
        setQ('');
        setCategory('');
        setDateFrom('');
        setDateTo('');
        setLocation('');
        setPriceMin('');
        setPriceMax('');
        setSort('');
        router.get(route('events.search'));
    }

    const hasFilters =
        q || category || dateFrom || dateTo || location || priceMin || priceMax;

    return (
        <PublicLayout>
            <Head title="Search Events" />

            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-6 text-3xl font-bold tracking-tight">
                    Search Events
                </h1>

                <form onSubmit={handleSearch} className="mb-6 space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="relative min-w-60 flex-1">
                            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                            <input
                                type="text"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Search events by name or description..."
                                className="border-border bg-background w-full rounded-lg border py-2 pr-4 pl-10 text-sm"
                            />
                        </div>
                        <button
                            type="submit"
                            className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-lg px-4 py-2 text-sm font-medium"
                        >
                            Search
                        </button>
                        <button
                            type="button"
                            onClick={() => setShowFilters(!showFilters)}
                            className="border-border hover:bg-muted flex items-center gap-1 rounded-lg border px-3 py-2 text-sm"
                        >
                            <Filter className="h-4 w-4" />
                            Filters
                        </button>
                        <select
                            value={sort}
                            onChange={(e) => {
                                setSort(e.target.value);
                                router.get(
                                    route('events.search'),
                                    {
                                        q,
                                        category,
                                        date_from: dateFrom,
                                        date_to: dateTo,
                                        location,
                                        price_min: priceMin,
                                        price_max: priceMax,
                                        sort: e.target.value,
                                    },
                                    { preserveState: true },
                                );
                            }}
                            className="border-border bg-background rounded-lg border px-3 py-2 text-sm"
                        >
                            <option value="">Sort: Newest</option>
                            <option value="date">Sort: Soonest</option>
                            <option value="price">Sort: Price (Low to High)</option>
                            <option value="popularity">
                                Sort: Popularity
                            </option>
                        </select>
                    </div>

                    {showFilters && (
                        <div className="border-border bg-card flex flex-wrap gap-3 rounded-lg border p-4">
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs">
                                    Category
                                </label>
                                <input
                                    type="text"
                                    value={category}
                                    onChange={(e) =>
                                        setCategory(e.target.value)
                                    }
                                    placeholder="e.g. music, sports"
                                    className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs">
                                    Location
                                </label>
                                <input
                                    type="text"
                                    value={location}
                                    onChange={(e) =>
                                        setLocation(e.target.value)
                                    }
                                    placeholder="City or venue"
                                    className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs">
                                    Min Price
                                </label>
                                <input
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={priceMin}
                                    onChange={(e) =>
                                        setPriceMin(e.target.value)
                                    }
                                    placeholder="0"
                                    className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs">
                                    Max Price
                                </label>
                                <input
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={priceMax}
                                    onChange={(e) =>
                                        setPriceMax(e.target.value)
                                    }
                                    placeholder="100"
                                    className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs">
                                    From
                                </label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) =>
                                        setDateFrom(e.target.value)
                                    }
                                    className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs">
                                    To
                                </label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="border-border bg-background rounded-md border px-3 py-1.5 text-sm"
                                />
                            </div>
                            {hasFilters && (
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="border-border hover:bg-muted flex items-center gap-1 self-end rounded-md border px-2 py-1.5 text-xs"
                                >
                                    <X className="h-3 w-3" />
                                    Clear
                                </button>
                            )}
                        </div>
                    )}
                </form>

                {events.length === 0 ? (
                    <div className="border-border rounded-lg border-2 border-dashed p-12 text-center">
                        <p className="text-muted-foreground">
                            No events found matching your criteria.
                        </p>
                    </div>
                ) : (
                    <>
                        <p className="text-muted-foreground mb-4 text-sm">
                            {pagination?.total ?? events.length} event
                            {pagination?.total === 1 ? '' : 's'} found
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {events.map((event) => (
                                <a
                                    key={event.id}
                                    href={route('events.show', event.slug)}
                                    className="group border-border bg-card hover:border-primary rounded-lg border transition-colors"
                                >
                                    {event.cover_url ? (
                                        <img
                                            src={event.cover_url}
                                            alt={event.title}
                                            className="h-40 w-full rounded-t-lg object-cover"
                                        />
                                    ) : (
                                        <div className="bg-muted flex h-40 items-center justify-center rounded-t-lg">
                                            <span className="text-muted-foreground">
                                                No image
                                            </span>
                                        </div>
                                    )}
                                    <div className="p-4">
                                        <h3 className="group-hover:text-primary font-semibold">
                                            {event.title}
                                        </h3>
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {event.organization_name}
                                        </p>
                                        {event.next_session_date && (
                                            <p className="text-muted-foreground mt-2 flex items-center gap-1 text-xs">
                                                <Calendar className="h-3 w-3" />
                                                {new Date(
                                                    event.next_session_date,
                                                ).toLocaleDateString()}
                                            </p>
                                        )}
                                        {event.category && (
                                            <span className="bg-primary/10 text-primary mt-2 inline-block rounded-full px-2 py-0.5 text-[10px]">
                                                {event.category}
                                            </span>
                                        )}
                                    </div>
                                </a>
                            ))}
                        </div>

                        {pagination && pagination.last_page > 1 && (
                            <div className="mt-8 flex items-center justify-center gap-2">
                                <button
                                    onClick={() =>
                                        goToPage(pagination.current_page - 1)
                                    }
                                    disabled={pagination.current_page <= 1}
                                    className="border-border hover:bg-muted flex items-center gap-1 rounded-lg border px-3 py-2 text-sm disabled:opacity-40"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Prev
                                </button>
                                {Array.from(
                                    { length: pagination.last_page },
                                    (_, i) => i + 1,
                                ).map((page) => (
                                    <button
                                        key={page}
                                        onClick={() => goToPage(page)}
                                        className={`rounded-lg border px-3 py-2 text-sm ${
                                            page === pagination.current_page
                                                ? 'bg-primary text-primary-foreground border-primary'
                                                : 'border-border hover:bg-muted'
                                        }`}
                                    >
                                        {page}
                                    </button>
                                ))}
                                <button
                                    onClick={() =>
                                        goToPage(pagination.current_page + 1)
                                    }
                                    disabled={
                                        pagination.current_page >=
                                        pagination.last_page
                                    }
                                    className="border-border hover:bg-muted flex items-center gap-1 rounded-lg border px-3 py-2 text-sm disabled:opacity-40"
                                >
                                    Next
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </div>
                        )}
                    </>
                )}
            </div>
        </PublicLayout>
    );
}