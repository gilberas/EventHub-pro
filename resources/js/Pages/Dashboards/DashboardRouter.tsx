import { getHighestRole } from '@/Config/dashboard';
import { Head, usePage } from '@inertiajs/react';
import CustomerDashboard from './CustomerDashboard';
import EventManagerDashboard from './EventManagerDashboard';
import FinanceManagerDashboard from './FinanceManagerDashboard';
import OrganizationAdminDashboard from './OrganizationAdminDashboard';
import OrganizationOwnerDashboard from './OrganizationOwnerDashboard';
import PlatformAdminDashboard from './PlatformAdminDashboard';
import SuperAdministratorDashboard from './SuperAdministratorDashboard';
import SupportAgentDashboard from './SupportAgentDashboard';
import TicketScannerDashboard from './TicketScannerDashboard';

export default function DashboardRouter() {
    const { auth } = usePage().props;
    const highestRole = getHighestRole(auth.user?.roles ?? []);

    switch (highestRole) {
        case 'SuperAdministrator':
            return <SuperAdministratorDashboard />;
        case 'PlatformAdmin':
            return <PlatformAdminDashboard />;
        case 'OrganizationOwner':
            return <OrganizationOwnerDashboard />;
        case 'OrganizationAdmin':
            return <OrganizationAdminDashboard />;
        case 'FinanceManager':
            return <FinanceManagerDashboard />;
        case 'EventManager':
            return <EventManagerDashboard />;
        case 'SupportAgent':
            return <SupportAgentDashboard />;
        case 'TicketScanner':
            return <TicketScannerDashboard />;
        case 'Customer':
            return <CustomerDashboard />;
        default:
            return (
                <div className="bg-background flex min-h-screen items-center justify-center">
                    <div className="text-center">
                        <Head title="Access Denied" />
                        <h1 className="text-foreground text-2xl font-bold">
                            Access Denied
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            You don't have permission to access the dashboard.
                        </p>
                    </div>
                </div>
            );
    }
}
