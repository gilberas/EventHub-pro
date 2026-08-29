import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Building2,
    Globe,
    HardDrive,
    Lock,
    Users,
} from 'lucide-react';

interface SuperAdminData {
    platform: {
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
    };
    failed_logins_24h: number;
    events_this_month: number;
    active_sessions_2h: number;
    system_health: Record<string, { status: string; load: string }>;
    maintenance_mode: boolean;
}

export default function SuperAdministratorDashboard() {
    const { data, loading, error } = useDashboardData<SuperAdminData>(
        route('dashboard.data.super-admin'),
    );

    return (
        <DashboardLayout>
            <Head title="Super Admin Dashboard" />
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    System Administration
                </h1>
                <p className="text-muted-foreground">
                    Full platform oversight, system health, and global
                    configuration.
                </p>
            </div>

            {loading ? (
                <div className="text-muted-foreground py-12 text-center">
                    Loading system data...
                </div>
            ) : error ? (
                <div className="py-12 text-center text-red-500">
                    Failed to load: {error}
                </div>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <WidgetCard
                            title="Platform Revenue"
                            value={`$${(data?.platform?.revenue?.total_revenue ?? 0).toLocaleString()}`}
                            description="All time"
                            icon={<Globe className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Active Orgs"
                            value={String(
                                data?.platform?.organizations?.active_orgs ?? 0,
                            )}
                            description={`${data?.platform?.organizations?.total_orgs ?? 0} total`}
                            icon={<Building2 className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Total Users"
                            value={(
                                data?.platform?.customer_growth
                                    ?.total_platform_users ?? 0
                            ).toLocaleString()}
                            description="Platform-wide"
                            icon={<Users className="h-4 w-4" />}
                            trend={{
                                value: `${data?.platform?.customer_growth?.growth_percent ?? 0}% growth`,
                                positive: true,
                            }}
                        />
                        <WidgetCard
                            title="System Health"
                            value={
                                Object.values(data?.system_health ?? {}).filter(
                                    (s: any) => s.status === 'Operational',
                                ).length + '/5'
                            }
                            description="Services operational"
                            icon={<Activity className="h-4 w-4" />}
                        />
                    </div>

                    <div className="mt-6 grid gap-4 lg:grid-cols-3">
                        <WidgetCard
                            title="System Status"
                            icon={<HardDrive className="h-4 w-4" />}
                            className="lg:col-span-2"
                        >
                            <div className="space-y-2">
                                {Object.entries(data?.system_health ?? {}).map(
                                    ([service, info]: [string, any]) => (
                                        <div
                                            key={service}
                                            className="border-border flex items-center justify-between rounded-lg border p-2.5"
                                        >
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className={`h-2 w-2 rounded-full ${info.status === 'Operational' ? 'bg-green-500' : 'bg-yellow-500'}`}
                                                />
                                                <p className="text-sm font-medium capitalize">
                                                    {service.replace('_', ' ')}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-muted-foreground text-xs">
                                                    Load: {info.load}
                                                </p>
                                                <p
                                                    className={`text-xs font-medium ${info.status === 'Operational' ? 'text-green-500' : 'text-yellow-500'}`}
                                                >
                                                    {info.status}
                                                </p>
                                            </div>
                                        </div>
                                    ),
                                )}
                            </div>
                        </WidgetCard>

                        <div className="space-y-4">
                            <WidgetCard
                                title="Security"
                                icon={<Lock className="h-4 w-4" />}
                            >
                                <div className="space-y-2">
                                    <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                        <p className="text-sm">
                                            Failed Logins (24h)
                                        </p>
                                        <p className="text-sm font-medium text-red-500">
                                            {data?.failed_logins_24h ?? 0}
                                        </p>
                                    </div>
                                    <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                        <p className="text-sm">
                                            Maintenance Mode
                                        </p>
                                        <p
                                            className={`text-sm font-medium ${data?.maintenance_mode ? 'text-yellow-500' : 'text-green-500'}`}
                                        >
                                            {data?.maintenance_mode
                                                ? 'ON'
                                                : 'OFF'}
                                        </p>
                                    </div>
                                    <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                        <p className="text-sm">
                                            Active Sessions (2h)
                                        </p>
                                        <p className="text-sm font-medium">
                                            {data?.active_sessions_2h ?? 0}
                                        </p>
                                    </div>
                                </div>
                            </WidgetCard>

                            <WidgetCard
                                title="Platform Metrics"
                                icon={<BarChart3 className="h-4 w-4" />}
                            >
                                <div className="space-y-2">
                                    <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                        <p className="text-sm">
                                            Events This Month
                                        </p>
                                        <p className="text-sm font-medium">
                                            {data?.events_this_month ?? 0}
                                        </p>
                                    </div>
                                    <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                        <p className="text-sm">Tickets Sold</p>
                                        <p className="text-sm font-medium">
                                            {(
                                                data?.platform
                                                    ?.total_tickets_sold ?? 0
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                    <div className="border-border flex items-center justify-between rounded-lg border p-2.5">
                                        <p className="text-sm">
                                            Avg Order Value
                                        </p>
                                        <p className="text-sm font-medium">
                                            $
                                            {(
                                                data?.platform?.revenue
                                                    ?.avg_order ?? 0
                                            ).toFixed(2)}
                                        </p>
                                    </div>
                                </div>
                            </WidgetCard>
                        </div>
                    </div>

                    <div className="mt-4">
                        <WidgetCard title="Quick Admin Actions">
                            <div className="flex flex-wrap gap-2">
                                <Link
                                    href={route('admin.system.health')}
                                    className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                                >
                                    System Health
                                </Link>
                                <Link
                                    href={route('admin.audit-log')}
                                    className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                                >
                                    View Audit Log
                                </Link>
                                <Link
                                    href={route('admin.organizations')}
                                    className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                                >
                                    Organizations
                                </Link>
                                <Link
                                    href={route('admin.users')}
                                    className="border-border hover:bg-muted rounded-md border px-3 py-1.5 text-xs font-medium"
                                >
                                    Users
                                </Link>
                            </div>
                        </WidgetCard>
                    </div>
                </>
            )}
        </DashboardLayout>
    );
}
