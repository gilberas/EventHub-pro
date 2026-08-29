import WidgetCard from '@/Components/Dashboard/WidgetCard';
import { Button } from '@/Components/ui/button';
import { useDashboardData } from '@/Hooks/useDashboardData';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link } from '@inertiajs/react';
import { Calendar, ScanLine, TicketCheck } from 'lucide-react';

interface ScannerData {
    scanned_today: number;
    valid_today: number;
    recent_scans: Array<{
        id: number;
        result: string;
        holder_name: string;
        ticket_type: string;
        time: string;
    }>;
}

export default function TicketScannerDashboard() {
    const { data, loading } = useDashboardData<ScannerData>(
        route('dashboard.data.scanner'),
    );

    return (
        <DashboardLayout>
            <Head title="Scanner Dashboard" />
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Scanning Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Scan and validate tickets for today's events.
                </p>
            </div>

            {loading ? (
                <div className="py-12 text-center text-gray-400">
                    Loading scan data...
                </div>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <WidgetCard
                            title="Scanned Today"
                            value={String(data?.scanned_today ?? 0)}
                            description="Total scans today"
                            icon={<Calendar className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Valid Scans"
                            value={String(data?.valid_today ?? 0)}
                            description="Validated entries"
                            icon={<TicketCheck className="h-4 w-4" />}
                        />
                        <WidgetCard
                            title="Open Scanner"
                            icon={<ScanLine className="h-4 w-4" />}
                        >
                            <Link
                                href={route('scanner')}
                                className="mt-2 block"
                            >
                                <Button size="lg" className="w-full gap-2">
                                    <ScanLine className="h-5 w-5" />
                                    Open Scanner
                                </Button>
                            </Link>
                        </WidgetCard>
                    </div>

                    <div className="mt-6">
                        <WidgetCard title="Recent Scans">
                            {(data?.recent_scans ?? []).length === 0 ? (
                                <p className="py-2 text-sm text-gray-400">
                                    No scans today yet.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {(data?.recent_scans ?? []).map((scan) => (
                                        <div
                                            key={scan.id}
                                            className="border-border flex items-center justify-between rounded-lg border p-2.5"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {scan.holder_name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {scan.ticket_type}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <span
                                                    className={`text-xs font-medium ${scan.result === 'valid' ? 'text-green-500' : 'text-red-500'}`}
                                                >
                                                    {scan.result}
                                                </span>
                                                <p className="text-muted-foreground text-xs">
                                                    {scan.time}
                                                </p>
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
