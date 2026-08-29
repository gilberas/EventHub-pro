import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Building2,
    Calendar,
    DollarSign,
    Settings,
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

export default function OrganizationAdminDashboard() {
    const { data, loading, error } = useDashboardData<OrgDashboardData>(
        route('dashboard.data.org'),
    );

    const cur = data?.org_currency ?? 'USD';

    return (
        <DashboardLayout>
            <Head title="Organization Admin Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Organization Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Manage {data?.org_name ?? 'your organization'}'s events,
                    users, and finances.
                </p>
            </div>

            {error && (
                <div className="border-destructive/40 bg-destructive/5 text-destructive mb-6 rounded-lg border p-4 text-sm">
                    Failed to load dashboard data: {error}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <WidgetCard
                    title="Total Events"
                    loading={loading}
                    value={String(data?.overview?.total_events ?? 0)}
                    description="All time events"
                    icon={<Calendar className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Staff Members"
                    loading={loading}
                    value={String(data?.staff_count ?? 0)}
                    description="Active staff accounts"
                    icon={<Users className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Revenue"
                    loading={loading}
                    value={`${cur} ${(data?.revenue?.total_revenue ?? 0).toLocaleString()}`}
                    description="All confirmed bookings"
                    icon={<DollarSign className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Active Venues"
                    loading={loading}
                    value={String(data?.overview?.total_venues ?? 0)}
                    description="Registered venues"
                    icon={<Building2 className="h-4 w-4" />}
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <WidgetCard
                    title="Customer Growth"
                    icon={<Users className="h-4 w-4" />}
                >
                    {loading ? (
                        <div className="space-y-2">
                            {[0, 1].map((i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-10 animate-pulse rounded-lg"
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">
                                    Total Customers
                                </p>
                                <p className="text-sm font-medium">
                                    {data?.customer_growth?.total_customers ??
                                        0}
                                </p>
                            </div>
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">
                                    New This Month
                                </p>
                                <p className="text-sm font-medium">
                                    {data?.customer_growth?.new_this_month ?? 0}
                                </p>
                            </div>
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">
                                    Attendance Rate
                                </p>
                                <p className="text-sm font-medium">
                                    {data?.attendance?.rate ?? 0}%
                                </p>
                            </div>
                        </div>
                    )}
                </WidgetCard>

                <WidgetCard
                    title="Quick Actions"
                    icon={<Activity className="h-4 w-4" />}
                >
                    <div className="space-y-2">
                        {data?.org_id && (
                            <>
                                <Link
                                    href={`/organizations/${data.org_id}/settings`}
                                    className="border-border hover:bg-muted block rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <Settings className="text-muted-foreground h-4 w-4" />
                                        <div>
                                            <p className="text-sm font-medium">
                                                Organization Settings
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Profile, staff & billing
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                                <Link
                                    href={`/organizations/${data.org_id}/settings?tab=staff`}
                                    className="border-border hover:bg-muted block rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <Users className="text-muted-foreground h-4 w-4" />
                                        <div>
                                            <p className="text-sm font-medium">
                                                Staff Management
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Roles & permissions
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                                <Link
                                    href={route('org.reports')}
                                    className="border-border hover:bg-muted block rounded-lg border p-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <BarChart3 className="text-muted-foreground h-4 w-4" />
                                        <div>
                                            <p className="text-sm font-medium">
                                                View Reports
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Revenue & sales analytics
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            </>
                        )}
                    </div>
                </WidgetCard>
            </div>
        </DashboardLayout>
    );
}
