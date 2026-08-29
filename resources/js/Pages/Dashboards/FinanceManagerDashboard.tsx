import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import {
    BarChart3,
    DollarSign,
    Receipt,
    RefreshCw,
    Wallet,
} from 'lucide-react';

interface FinanceData {
    revenue: {
        total_revenue: number;
        booking_count: number;
        avg_order_value: number;
        fees: number;
    };
    refunds: {
        total_refunds: number;
        refund_count: number;
        refund_rate: number;
    };
    pending_refunds: number;
    invoice_count: number;
    recent_transactions: Array<{
        id: string;
        event: string;
        customer: string;
        amount: number;
        status: string;
        created_at: string;
    }>;
    org_currency: string;
}

export default function FinanceManagerDashboard() {
    const { data, loading } = useDashboardData<FinanceData>(
        route('dashboard.data.finance'),
    );

    const cur = data?.org_currency ?? 'USD';

    return (
        <DashboardLayout>
            <Head title="Finance Dashboard" />
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Finance Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Monitor revenue, payouts, and financial reports.
                </p>
            </div>

            {loading ? (
                <div className="py-12 text-center text-gray-400">
                    Loading financial data...
                </div>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <WidgetCard
                            title="Total Revenue"
                            value={`${cur} ${(data?.revenue?.total_revenue ?? 0).toLocaleString()}`}
                            description="All confirmed bookings"
                            icon={<DollarSign className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Avg Order Value"
                            value={`${cur} ${(data?.revenue?.avg_order_value ?? 0).toFixed(2)}`}
                            description="Per booking"
                            icon={<Wallet className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Refunds"
                            value={`${cur} ${(data?.refunds?.total_refunds ?? 0).toFixed(2)}`}
                            description={`${data?.refunds?.refund_count ?? 0} transactions`}
                            icon={<RefreshCw className="h-4 w-4" />}
                            trend={{
                                value: `${(data?.refunds?.refund_rate ?? 0).toFixed(1)}% refund rate`,
                                positive: false,
                            }}
                        />
                        <WidgetCard
                            title="Invoices"
                            value={String(data?.invoice_count ?? 0)}
                            description="Total invoices"
                            icon={<Receipt className="h-4 w-4" />}
                        />
                    </div>

                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                        <WidgetCard title="Revenue Summary">
                            <div className="space-y-2">
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm font-medium">
                                        Total Revenue
                                    </p>
                                    <p className="text-sm font-medium">
                                        {cur}{' '}
                                        {(
                                            data?.revenue?.total_revenue ?? 0
                                        ).toFixed(2)}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm font-medium">
                                        Booking Count
                                    </p>
                                    <p className="text-sm font-medium">
                                        {data?.revenue?.booking_count ?? 0}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm font-medium">
                                        Fees Collected
                                    </p>
                                    <p className="text-sm font-medium">
                                        {cur}{' '}
                                        {(data?.revenue?.fees ?? 0).toFixed(2)}
                                    </p>
                                </div>
                                <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                    <p className="text-sm font-medium">
                                        Pending Refunds
                                    </p>
                                    <p className="text-sm font-medium">
                                        {data?.pending_refunds ?? 0}
                                    </p>
                                </div>
                            </div>
                        </WidgetCard>

                        <WidgetCard title="Recent Transactions">
                            <div className="space-y-2">
                                {(data?.recent_transactions ?? []).length ===
                                0 ? (
                                    <p className="text-sm text-gray-400">
                                        No transactions yet.
                                    </p>
                                ) : (
                                    (data?.recent_transactions ?? [])
                                        .slice(0, 5)
                                        .map((txn) => (
                                            <div
                                                key={txn.id}
                                                className="border-border flex items-center justify-between rounded-lg border p-2.5"
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium">
                                                        {txn.event}
                                                    </p>
                                                    <p className="text-muted-foreground truncate text-xs">
                                                        {txn.customer}
                                                    </p>
                                                </div>
                                                <div className="ml-2 shrink-0 text-right">
                                                    <p className="text-sm font-medium">
                                                        {cur}{' '}
                                                        {txn.amount.toFixed(2)}
                                                    </p>
                                                    <span
                                                        className={`text-xs font-medium ${
                                                            txn.status ===
                                                            'confirmed'
                                                                ? 'text-green-500'
                                                                : txn.status ===
                                                                    'refunded'
                                                                  ? 'text-red-500'
                                                                  : 'text-yellow-500'
                                                        }`}
                                                    >
                                                        {txn.status.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                        ))
                                )}
                            </div>
                        </WidgetCard>
                    </div>
                </>
            )}

            <div className="mt-4">
                <WidgetCard
                    title="Quick Actions"
                    icon={<BarChart3 className="h-4 w-4" />}
                >
                    <div className="flex flex-wrap gap-2">
                        <span className="border-border hover:bg-muted cursor-pointer rounded-md border px-3 py-1.5 text-xs font-medium">
                            Generate Report
                        </span>
                        <span className="border-border hover:bg-muted cursor-pointer rounded-md border px-3 py-1.5 text-xs font-medium">
                            Export Data
                        </span>
                        <span className="border-border hover:bg-muted cursor-pointer rounded-md border px-3 py-1.5 text-xs font-medium">
                            Process Payouts
                        </span>
                        <span className="border-border hover:bg-muted cursor-pointer rounded-md border px-3 py-1.5 text-xs font-medium">
                            Review Refunds
                        </span>
                    </div>
                </WidgetCard>
            </div>
        </DashboardLayout>
    );
}
