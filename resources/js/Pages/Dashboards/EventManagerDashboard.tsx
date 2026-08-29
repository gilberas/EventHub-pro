import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import { Calendar, DollarSign, FileText, MapPin, Users } from 'lucide-react';

interface OrgDashboardData {
    revenue: {
        total_revenue: number;
        booking_count: number;
        avg_order_value: number;
        fees_collected: number;
    };
    attendance: { total: number; checked_in: number; rate: number };
    refunds: {
        total_refunds: number;
        refund_count: number;
        refund_rate: number;
    };
    customer_growth: {
        total_customers: number;
        new_this_month: number;
        growth_percent: number;
    };
    overview: {
        total_events: number;
        active_events: number;
        total_venues: number;
        total_ticket_types: number;
    };
    popular_events: Array<{
        event_id: number;
        title: string;
        total_revenue: number;
        tickets_sold: number;
    }>;
    upcoming_events: Array<{
        event_id: number;
        title: string;
        status: string;
        next_session: string | null;
        tickets_sold: number;
    }>;
    staff_count: number;
    org_id: number;
    org_name: string;
    org_currency: string;
}

export default function EventManagerDashboard() {
    const { data, loading, error } = useDashboardData<OrgDashboardData>(
        route('dashboard.data.org'),
    );

    const cur = data?.org_currency ?? 'USD';

    return (
        <DashboardLayout>
            <Head title="Event Manager Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Event Manager Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Create and manage your events in one place.
                </p>
            </div>

            {error && (
                <div className="border-destructive/40 bg-destructive/5 text-destructive mb-6 rounded-lg border p-4 text-sm">
                    Failed to load dashboard data: {error}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <WidgetCard
                    title="Active Events"
                    loading={loading}
                    value={String(data?.overview?.active_events ?? 0)}
                    description="Published events"
                    icon={<Calendar className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Total Attendees"
                    loading={loading}
                    value={(data?.attendance?.total ?? 0).toLocaleString()}
                    description={`${data?.attendance?.checked_in ?? 0} checked in`}
                    icon={<Users className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Venues"
                    loading={loading}
                    value={String(data?.overview?.total_venues ?? 0)}
                    description="Registered venues"
                    icon={<MapPin className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Revenue"
                    loading={loading}
                    value={`${cur} ${(data?.revenue?.total_revenue ?? 0).toLocaleString()}`}
                    description="Total ticket sales"
                    icon={<DollarSign className="h-4 w-4" />}
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <WidgetCard
                    title="Upcoming Events"
                    icon={<Calendar className="h-4 w-4" />}
                >
                    {loading ? (
                        <div className="space-y-2">
                            {[0, 1, 2].map((i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-12 animate-pulse rounded-lg"
                                />
                            ))}
                        </div>
                    ) : (data?.upcoming_events ?? []).length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No upcoming published events.
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {(data?.upcoming_events ?? []).map((event) => (
                                <div
                                    key={event.event_id}
                                    className="border-border flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {event.title}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {event.next_session ?? 'TBD'} ·{' '}
                                            {event.tickets_sold} sold
                                        </p>
                                    </div>
                                    <span
                                        className={`ml-2 shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${
                                            event.status === 'published'
                                                ? 'bg-green-500/10 text-green-500'
                                                : 'bg-yellow-500/10 text-yellow-500'
                                        }`}
                                    >
                                        {event.status === 'published'
                                            ? 'Published'
                                            : 'Draft'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </WidgetCard>

                <WidgetCard
                    title="Top Selling Events"
                    icon={<DollarSign className="h-4 w-4" />}
                >
                    {loading ? (
                        <div className="space-y-2">
                            {[0, 1, 2].map((i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-12 animate-pulse rounded-lg"
                                />
                            ))}
                        </div>
                    ) : (data?.popular_events ?? []).length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No sales data yet.
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {(data?.popular_events ?? []).map((event) => (
                                <div
                                    key={event.event_id}
                                    className="border-border flex items-center justify-between rounded-lg border p-2.5"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {event.title}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {event.tickets_sold} tickets sold
                                        </p>
                                    </div>
                                    <span className="bg-primary/10 text-primary ml-2 shrink-0 rounded-full px-2 py-0.5 text-xs font-medium">
                                        {cur}{' '}
                                        {event.total_revenue.toLocaleString()}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </WidgetCard>
            </div>

            <div className="mt-6">
                <WidgetCard
                    title="Quick Links"
                    icon={<FileText className="h-4 w-4" />}
                >
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('org.events.index')}
                            className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                        >
                            Manage Events
                        </Link>
                        <Link
                            href={route('org.events.create')}
                            className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                        >
                            Create Event
                        </Link>
                        <Link
                            href={route('org.reports')}
                            className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                        >
                            View Reports
                        </Link>
                    </div>
                </WidgetCard>
            </div>
        </DashboardLayout>
    );
}
