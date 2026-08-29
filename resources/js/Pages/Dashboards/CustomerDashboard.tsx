import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import { Calendar, ShoppingCart, Ticket } from 'lucide-react';

interface CustomerData {
    upcoming_events: number;
    active_orders: number;
    ticket_count: number;
    pending_bookings: number;
    recent_orders: Array<{
        id: number;
        reference: string;
        event: string;
        date: string;
        status: string;
        amount: number;
    }>;
}

export default function CustomerDashboard() {
    const { data, loading } = useDashboardData<CustomerData>(
        route('dashboard.data.customer'),
    );

    return (
        <DashboardLayout>
            <Head title="My Dashboard" />
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    My Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Welcome back! Here's what's happening with your events.
                </p>
            </div>

            {loading ? (
                <div className="py-12 text-center text-gray-400">
                    Loading your data...
                </div>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <WidgetCard
                            title="Upcoming Events"
                            value={String(data?.upcoming_events ?? 0)}
                            description="Events you're attending"
                            icon={<Calendar className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Active Orders"
                            value={String(data?.active_orders ?? 0)}
                            description="Confirmed bookings"
                            icon={<ShoppingCart className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="My Tickets"
                            value={String(data?.ticket_count ?? 0)}
                            description="Across all events"
                            icon={<Ticket className="h-4 w-4" />}
                        />
                    </div>

                    <div className="mt-6">
                        <WidgetCard
                            title="Recent Orders"
                            className="lg:col-span-2"
                        >
                            {(data?.recent_orders ?? []).length === 0 ? (
                                <p className="py-2 text-sm text-gray-400">
                                    No orders yet. Browse events to get started!
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {(data?.recent_orders ?? [])
                                        .slice(0, 5)
                                        .map((order) => (
                                            <div
                                                key={order.id}
                                                className="border-border flex items-center justify-between rounded-lg border p-3"
                                            >
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {order.event}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        {order.date}
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-medium">
                                                        $
                                                        {order.amount.toFixed(
                                                            2,
                                                        )}
                                                    </p>
                                                    <span
                                                        className={`text-xs font-medium capitalize ${
                                                            order.status ===
                                                            'confirmed'
                                                                ? 'text-green-500'
                                                                : order.status ===
                                                                    'pending_payment'
                                                                  ? 'text-yellow-500'
                                                                  : 'text-gray-500'
                                                        }`}
                                                    >
                                                        {order.status.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                </div>
                            )}
                        </WidgetCard>
                    </div>
                </>
            )}
        </DashboardLayout>
    );
}
