import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import { Activity, BarChart3, Building2, Shield, Users } from 'lucide-react';

interface PlatformData {
    revenue: {
        total_revenue: number;
        total_bookings: number;
        total_fees: number;
        avg_order: number;
    };
    organizations: {
        total_orgs: number;
        active_orgs: number;
        suspended_orgs: number;
    };
    customer_growth: {
        total_platform_users: number;
        new_this_month: number;
        growth_percent: number;
    };
    total_events: number;
    total_tickets_sold: number;
    monthly_revenue: Record<string, number>;
}

export default function PlatformAdminDashboard() {
    const { data, loading } = useDashboardData<PlatformData>(
        route('dashboard.data.platform'),
    );

    return (
        <DashboardLayout>
            <Head title="Platform Admin Dashboard" />
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Platform Admin Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Manage organizations, users, and system configuration.
                </p>
            </div>

            {loading ? (
                <div className="py-12 text-center text-gray-400">
                    Loading platform data...
                </div>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <WidgetCard
                            title="Organizations"
                            value={String(data?.organizations?.total_orgs ?? 0)}
                            description={`${data?.organizations?.active_orgs ?? 0} active`}
                            icon={<Building2 className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Total Users"
                            value={(
                                data?.customer_growth?.total_platform_users ?? 0
                            ).toLocaleString()}
                            description="Platform-wide"
                            icon={<Users className="h-4 w-4" />}
                            trend={{
                                value: `${data?.customer_growth?.growth_percent ?? 0}% growth`,
                                positive: true,
                            }}
                        />
                        <WidgetCard
                            title="Total Revenue"
                            value={`$${(data?.revenue?.total_revenue ?? 0).toLocaleString()}`}
                            description="All time"
                            icon={<Shield className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Total Events"
                            value={String(data?.total_events ?? 0)}
                            description="Platform-wide"
                            icon={<BarChart3 className="h-4 w-4" />}
                        />
                    </div>

                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                        <WidgetCard title="Platform Overview">
                            <div className="space-y-2">
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm">
                                        Active Organizations
                                    </p>
                                    <p className="text-sm font-medium text-green-600">
                                        {data?.organizations?.active_orgs ?? 0}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm">Suspended</p>
                                    <p className="text-sm font-medium text-red-600">
                                        {data?.organizations?.suspended_orgs ??
                                            0}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm">Total Bookings</p>
                                    <p className="text-sm font-medium">
                                        {data?.revenue?.total_bookings ?? 0}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm">Avg Order Value</p>
                                    <p className="text-sm font-medium">
                                        $
                                        {(
                                            data?.revenue?.avg_order ?? 0
                                        ).toFixed(2)}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm">Tickets Sold</p>
                                    <p className="text-sm font-medium">
                                        {data?.total_tickets_sold ?? 0}
                                    </p>
                                </div>
                            </div>
                        </WidgetCard>

                        <WidgetCard
                            title="Growth"
                            icon={<Activity className="h-4 w-4" />}
                        >
                            <div className="space-y-2">
                                <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                    <p className="text-sm">
                                        New Users This Month
                                    </p>
                                    <p className="text-sm font-medium">
                                        +
                                        {data?.customer_growth
                                            ?.new_this_month ?? 0}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                    <p className="text-sm">Fees Collected</p>
                                    <p className="text-sm font-medium">
                                        $
                                        {(
                                            data?.revenue?.total_fees ?? 0
                                        ).toLocaleString()}
                                    </p>
                                </div>
                            </div>
                        </WidgetCard>
                    </div>
                </>
            )}
        </DashboardLayout>
    );
}
