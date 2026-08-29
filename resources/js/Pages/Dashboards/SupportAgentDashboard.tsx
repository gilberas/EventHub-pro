import WidgetCard from '@/Components/Dashboard/WidgetCard';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import { CheckCircle2, Clock, MessageSquare, Tickets } from 'lucide-react';

export default function SupportAgentDashboard() {
    return (
        <DashboardLayout>
            <Head title="Support Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Support Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Manage support tickets and help customers.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <WidgetCard
                    title="Open Tickets"
                    value="12"
                    description="Awaiting response"
                    icon={<Tickets className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Active Chats"
                    value="3"
                    description="Currently in conversation"
                    icon={<MessageSquare className="h-4 w-4" />}
                />
                <WidgetCard
                    title="Avg Response"
                    value="4.2m"
                    description="Average response time"
                    icon={<Clock className="h-4 w-4" />}
                    trend={{ value: '30s improvement', positive: true }}
                />
                <WidgetCard
                    title="Resolved Today"
                    value="18"
                    description="Tickets closed today"
                    icon={<CheckCircle2 className="h-4 w-4" />}
                    trend={{ value: '22% more than yesterday', positive: true }}
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <WidgetCard title="Open Tickets" className="lg:col-span-2">
                    <div className="space-y-2">
                        {/* PLACEHOLDER: Replace with real ticket data from Phase 6 */}
                        {[
                            {
                                subject: 'Cannot access my ticket QR code',
                                customer: 'john@email.com',
                                priority: 'High',
                                time: '10m ago',
                            },
                            {
                                subject: 'Refund request for cancelled event',
                                customer: 'sarah@email.com',
                                priority: 'Medium',
                                time: '25m ago',
                            },
                            {
                                subject: 'How do I transfer my ticket?',
                                customer: 'mike@email.com',
                                priority: 'Low',
                                time: '1h ago',
                            },
                            {
                                subject: 'VIP upgrade not showing in app',
                                customer: 'emma@email.com',
                                priority: 'High',
                                time: '2h ago',
                            },
                        ].map((ticket) => (
                            <div
                                key={ticket.subject}
                                className="border-border flex items-center justify-between rounded-lg border p-3"
                            >
                                <div className="flex-1">
                                    <p className="text-sm font-medium">
                                        {ticket.subject}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {ticket.customer} · {ticket.time}
                                    </p>
                                </div>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                        ticket.priority === 'High'
                                            ? 'bg-red-500/10 text-red-500'
                                            : ticket.priority === 'Medium'
                                              ? 'bg-yellow-500/10 text-yellow-500'
                                              : 'bg-green-500/10 text-green-500'
                                    }`}
                                >
                                    {ticket.priority}
                                </span>
                            </div>
                        ))}
                    </div>
                </WidgetCard>
            </div>
        </DashboardLayout>
    );
}
