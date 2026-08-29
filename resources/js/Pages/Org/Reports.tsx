import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import { BarChart3, DollarSign, Receipt, TrendingUp } from 'lucide-react';

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
    monthly_revenue: Record<string, number>;
    sales_by_event: {
        total_revenue: number;
        booking_count: number;
        fees: number;
        refunds: number;
    };
    org_id: number;
    org_name: string;
    org_currency: string;
}

const MONTH_LABELS: Record<string, string> = {
    '01': 'Jan',
    '02': 'Feb',
    '03': 'Mar',
    '04': 'Apr',
    '05': 'May',
    '06': 'Jun',
    '07': 'Jul',
    '08': 'Aug',
    '09': 'Sep',
    '10': 'Oct',
    '11': 'Nov',
    '12': 'Dec',
};

export default function OrgReports() {
    const { data, loading, error } = useDashboardData<OrgDashboardData>(
        route('dashboard.data.org'),
    );

    const cur = data?.org_currency ?? 'USD';
    const monthly = Object.entries(data?.monthly_revenue ?? {});
    const maxRev = Math.max(1, ...monthly.map(([, v]) => v));

    return (
        <DashboardLayout>
            <Head title="Organization Reports" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Reports &amp; Analytics
                </h1>
                <p className="text-muted-foreground">
                    Revenue trends and sales performance for{' '}
                    {data?.org_name ?? 'your organization'}.
                </p>
            </div>

            {error && (
                <div className="border-destructive/40 bg-destructive/5 text-destructive mb-6 rounded-lg border p-4 text-sm">
                    Failed to load report data: {error}
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
                    title="Bookings"
                    loading={loading}
                    value={String(data?.revenue?.booking_count ?? 0)}
                    description="Total orders"
                    icon={<Receipt className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Avg Order Value"
                    loading={loading}
                    value={`${cur} ${(data?.revenue?.avg_order_value ?? 0).toFixed(2)}`}
                    description="Per booking"
                    icon={<TrendingUp className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Attendance Rate"
                    loading={loading}
                    value={`${data?.attendance?.rate ?? 0}%`}
                    description={`${data?.attendance?.checked_in ?? 0} of ${data?.attendance?.total ?? 0} checked in`}
                    icon={<BarChart3 className="h-4 w-4" />}
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <WidgetCard
                    title="Monthly Revenue (last 6 months)"
                    icon={<BarChart3 className="h-4 w-4" />}
                >
                    {loading ? (
                        <div className="space-y-2">
                            {[0, 1, 2].map((i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-24 animate-pulse rounded-lg"
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex h-48 items-end gap-2">
                            {monthly.map(([month, value]) => (
                                <div
                                    key={month}
                                    className="flex flex-1 flex-col items-center gap-1"
                                >
                                    <span className="text-muted-foreground text-[10px]">
                                        {value > 0
                                            ? `${cur} ${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`
                                            : ''}
                                    </span>
                                    <div
                                        className="bg-primary/80 w-full rounded-t-md"
                                        style={{
                                            height: `${Math.max(4, (value / maxRev) * 100)}%`,
                                        }}
                                    />
                                    <span className="text-muted-foreground text-xs">
                                        {MONTH_LABELS[month.slice(5, 7)] ??
                                            month.slice(5)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </WidgetCard>

                <WidgetCard
                    title="Sales Summary"
                    icon={<DollarSign className="h-4 w-4" />}
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
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">
                                    Gross Revenue
                                </p>
                                <p className="text-sm font-medium">
                                    {cur}{' '}
                                    {(
                                        data?.sales_by_event?.total_revenue ?? 0
                                    ).toFixed(2)}
                                </p>
                            </div>
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">
                                    Fees Collected
                                </p>
                                <p className="text-sm font-medium">
                                    {cur}{' '}
                                    {(data?.sales_by_event?.fees ?? 0).toFixed(
                                        2,
                                    )}
                                </p>
                            </div>
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">Refunds</p>
                                <p className="text-sm font-medium">
                                    {cur}{' '}
                                    {(
                                        data?.sales_by_event?.refunds ?? 0
                                    ).toFixed(2)}
                                </p>
                            </div>
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <p className="text-sm font-medium">
                                    Refund Rate
                                </p>
                                <p className="text-sm font-medium">
                                    {data?.refunds?.refund_rate ?? 0}%
                                </p>
                            </div>
                        </div>
                    )}
                </WidgetCard>
            </div>

            <div className="mt-6">
                <WidgetCard
                    title="Sales by Event"
                    icon={<TrendingUp className="h-4 w-4" />}
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
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-border text-muted-foreground border-b text-left text-xs tracking-wider uppercase">
                                        <th className="py-2 pr-4 font-medium">
                                            Event
                                        </th>
                                        <th className="py-2 pr-4 font-medium">
                                            Tickets Sold
                                        </th>
                                        <th className="py-2 text-right font-medium">
                                            Revenue
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(data?.popular_events ?? []).map(
                                        (event) => (
                                            <tr
                                                key={event.event_id}
                                                className="border-border/50 border-b"
                                            >
                                                <td className="py-2.5 pr-4 font-medium">
                                                    {event.title}
                                                </td>
                                                <td className="text-muted-foreground py-2.5 pr-4">
                                                    {event.tickets_sold}
                                                </td>
                                                <td className="py-2.5 text-right font-medium">
                                                    {cur}{' '}
                                                    {event.total_revenue.toLocaleString()}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </WidgetCard>
            </div>
        </DashboardLayout>
    );
}
