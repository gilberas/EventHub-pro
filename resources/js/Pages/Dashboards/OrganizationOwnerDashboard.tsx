import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    Calendar,
    CreditCard,
    DollarSign,
    Users,
} from 'lucide-react';

interface OrgDashboardData {
    revenue: {
        total_revenue: number;
        booking_count: number;
        avg_order_value: number;
        fees_collected: number;
    };
    attendance: { total: number; checked_in: number; rate: number };
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
    monthly_revenue: Record<string, number>;
    sales_by_event: {
        total_revenue: number;
        booking_count: number;
        fees: number;
        refunds: number;
    };
    staff_count: number;
    org_id: number;
    org_name: string;
    org_currency: string;
}

export default function OrganizationOwnerDashboard() {
    const { data, loading, error } = useDashboardData<OrgDashboardData>(
        route('dashboard.data.org'),
    );

    const cur = data?.org_currency ?? 'USD';
    const monthly = Object.values(data?.monthly_revenue ?? {});
    const monthlyRevenue =
        monthly.length > 0 ? Number(monthly[monthly.length - 1]) : 0;

    const revenueBreakdown = [
        {
            source: 'Total Revenue',
            amount: (data?.sales_by_event?.total_revenue ?? 0).toFixed(2),
            percentage: '100%',
        },
        {
            source: 'Fees Collected',
            amount: (data?.revenue?.fees_collected ?? 0).toFixed(2),
            percentage: '100%',
        },
        {
            source: 'Refunds',
            amount: (data?.sales_by_event?.refunds ?? 0).toFixed(2),
            percentage: '100%',
        },
        {
            source: 'Avg Order Value',
            amount: (data?.revenue?.avg_order_value ?? 0).toFixed(2),
            percentage: '100%',
        },
    ];

    return (
        <DashboardLayout>
            <Head title="Owner Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Business Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Full oversight of {data?.org_name ?? 'your organization'}'s
                    operations and finances.
                </p>
            </div>

            {error && (
                <div className="border-destructive/40 bg-destructive/5 text-destructive mb-6 rounded-lg border p-4 text-sm">
                    Failed to load dashboard data: {error}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <WidgetCard
                    title="Total Revenue"
                    loading={loading}
                    value={`${cur} ${(data?.revenue?.total_revenue ?? 0).toLocaleString()}`}
                    description="All confirmed bookings"
                    icon={<DollarSign className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Monthly Revenue"
                    loading={loading}
                    value={`${cur} ${monthlyRevenue.toLocaleString()}`}
                    description="Last month"
                    icon={<CreditCard className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Total Staff"
                    loading={loading}
                    value={String(data?.staff_count ?? 0)}
                    description="Team members"
                    icon={<Users className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Active Events"
                    loading={loading}
                    value={String(data?.overview?.active_events ?? 0)}
                    description="Currently published"
                    icon={<Calendar className="h-4 w-4" />}
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-3">
                <WidgetCard
                    title="Revenue Summary"
                    className="lg:col-span-2"
                    icon={<BarChart3 className="h-4 w-4" />}
                >
                    {loading ? (
                        <div className="space-y-2">
                            {[0, 1, 2, 3].map((i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-10 animate-pulse rounded-lg"
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="space-y-2">
                            {revenueBreakdown.map((item) => (
                                <div
                                    key={item.source}
                                    className="border-border flex items-center gap-3 rounded-lg border p-3"
                                >
                                    <div className="flex-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium">
                                                {item.source}
                                            </p>
                                            <p className="text-sm font-medium">
                                                {cur} {item.amount}
                                            </p>
                                        </div>
                                        <div className="bg-muted mt-1 h-2 w-full rounded-full">
                                            <div
                                                className="bg-primary h-2 rounded-full"
                                                style={{
                                                    width: item.percentage,
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </WidgetCard>

                <WidgetCard
                    title="Quick Actions"
                    icon={<BarChart3 className="h-4 w-4" />}
                >
                    <div className="space-y-2">
                        {data?.org_id && (
                            <>
                                <Link
                                    href={`/organizations/${data.org_id}/settings`}
                                    className="border-border hover:bg-muted block rounded-lg border p-3"
                                >
                                    <p className="text-sm font-medium">
                                        Organization Settings
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        Manage profile, staff & billing
                                    </p>
                                </Link>
                                <Link
                                    href={`/organizations/${data.org_id}/settings?tab=staff`}
                                    className="border-border hover:bg-muted block rounded-lg border p-3"
                                >
                                    <p className="text-sm font-medium">
                                        Staff Management
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        Roles & permissions
                                    </p>
                                </Link>
                                <Link
                                    href={route('org.reports')}
                                    className="border-border hover:bg-muted block rounded-lg border p-3"
                                >
                                    <p className="text-sm font-medium">
                                        View Reports
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        Monthly revenue & sales analytics
                                    </p>
                                </Link>
                            </>
                        )}
                    </div>
                </WidgetCard>
            </div>

            <div className="mt-4">
                <WidgetCard
                    title="Top Revenue Events"
                    icon={<Building2 className="h-4 w-4" />}
                >
                    {loading ? (
                        <div className="space-y-2">
                            {[0, 1, 2].map((i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-14 animate-pulse rounded-lg"
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
                                    className="border-border flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {event.title}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {event.tickets_sold} tickets sold
                                        </p>
                                    </div>
                                    <div className="ml-2 shrink-0 text-right">
                                        <p className="text-sm font-medium">
                                            {cur}{' '}
                                            {event.total_revenue.toLocaleString()}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </WidgetCard>
            </div>
        </DashboardLayout>
    );
}
