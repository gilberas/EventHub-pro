import WidgetCard from '@/Components/Dashboard/WidgetCard';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Pencil, Plus, Send, Trash2, X } from 'lucide-react';

interface EventSession {
    id: number;
    title: string | null;
    start_date: string;
    end_date: string;
    location: string | null;
    capacity: number | null;
}

interface ManagedEvent {
    id: number;
    organization_id: number;
    title: string;
    slug: string;
    description: string | null;
    category: string | null;
    status: string;
    is_featured: boolean;
    trending_score: number | null;
    cover_url: string | null;
    organization_name: string | null;
    next_session_date: string | null;
    sessions: EventSession[] | null;
}

interface Props {
    events: ManagedEvent[];
}

const statusStyles: Record<string, string> = {
    draft: 'bg-amber-500/15 text-amber-600',
    published: 'bg-emerald-500/15 text-emerald-600',
    cancelled: 'bg-red-500/15 text-red-600',
    completed: 'bg-slate-500/15 text-slate-600',
};

function formatDate(iso: string | null): string {
    if (!iso) return 'TBD';

    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function EventIndex({ events }: Props) {
    function toggleStatus(event: ManagedEvent, status: string) {
        router.post(route('org.events.toggle-status', event.id), { status });
    }

    function destroyEvent(event: ManagedEvent) {
        if (window.confirm(`Delete "${event.title}"? This cannot be undone.`)) {
            router.delete(route('events.destroy', event.id));
        }
    }

    return (
        <DashboardLayout>
            <Head title="My Events" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        My Events
                    </h1>
                    <p className="text-muted-foreground">
                        Create, publish, and manage your organization's events.
                    </p>
                </div>
                <Link
                    href={route('org.events.create')}
                    className="bg-primary text-primary-foreground hover:bg-primary/90 flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium"
                >
                    <Plus className="h-4 w-4" />
                    Create Event
                </Link>
            </div>

            {events.length === 0 ? (
                <div className="border-border flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 text-center">
                    <Calendar className="text-muted-foreground mb-2 h-8 w-8" />
                    <p className="text-muted-foreground text-sm">
                        No events yet. Create your first event to get started.
                    </p>
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {events.map((event) => (
                        <WidgetCard key={event.id} title={event.title}>
                            <div className="from-brand-700 to-accent-900 relative flex h-36 items-center justify-center overflow-hidden rounded-md bg-gradient-to-br">
                                {event.cover_url ? (
                                    <img
                                        src={event.cover_url}
                                        alt={event.title}
                                        loading="lazy"
                                        decoding="async"
                                        className="absolute inset-0 h-full w-full object-cover"
                                    />
                                ) : (
                                    <Calendar className="h-10 w-10 text-white/30" />
                                )}
                            </div>
                            <div className="mt-3 flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    {event.category ?? 'General'}
                                </span>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${statusStyles[event.status] ?? 'bg-muted text-muted-foreground'}`}
                                >
                                    {event.status.charAt(0).toUpperCase() +
                                        event.status.slice(1)}
                                </span>
                            </div>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {event.sessions?.length ?? 0} session(s) · Next:{' '}
                                {formatDate(event.next_session_date)}
                            </p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <Link
                                    href={`/events/${event.slug}`}
                                    className="bg-primary/10 text-primary hover:bg-primary/20 flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium"
                                >
                                    <Send className="h-3 w-3" />
                                    View
                                </Link>
                                <Link
                                    href={route('org.events.edit', event.id)}
                                    className="border-border hover:bg-muted flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium"
                                >
                                    <Pencil className="h-3 w-3" />
                                    Edit
                                </Link>
                                {event.status === 'draft' && (
                                    <button
                                        onClick={() =>
                                            toggleStatus(event, 'published')
                                        }
                                        className="flex items-center gap-1 rounded-md bg-emerald-500/15 px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-500/25"
                                    >
                                        <Send className="h-3 w-3" />
                                        Publish
                                    </button>
                                )}
                                {event.status === 'published' && (
                                    <button
                                        onClick={() =>
                                            toggleStatus(event, 'draft')
                                        }
                                        className="border-border hover:bg-muted flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium"
                                    >
                                        <X className="h-3 w-3" />
                                        Unpublish
                                    </button>
                                )}
                                <button
                                    onClick={() => destroyEvent(event)}
                                    className="border-border flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium text-red-500 hover:bg-red-500/10"
                                >
                                    <Trash2 className="h-3 w-3" />
                                    Delete
                                </button>
                            </div>
                        </WidgetCard>
                    ))}
                </div>
            )}
        </DashboardLayout>
    );
}
