import Sidebar from '@/Components/Dashboard/Sidebar';
import Topbar from '@/Components/Dashboard/Topbar';
import {
    getHighestRole,
    getRoleConfig,
    type NavGroup,
} from '@/Config/dashboard';
import { router, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface DashboardLayoutProps {
    children?: ReactNode;
}

export default function DashboardLayout({ children }: DashboardLayoutProps) {
    const { auth, ziggy } = usePage().props;
    const user = auth.user;

    const highestRole = getHighestRole(user?.roles ?? []);
    const roleConfig = highestRole ? getRoleConfig(highestRole) : null;
    const navGroups: NavGroup[] = roleConfig?.navGroups ?? [];

    const currentPath = ziggy.location ? new URL(ziggy.location).pathname : '/';

    function handleLogout() {
        router.post(route('logout'));
    }

    return (
        <div className="bg-background text-foreground flex h-screen">
            <Sidebar
                navGroups={navGroups}
                currentPath={currentPath}
                onLogout={handleLogout}
                currentOrgId={auth.current_organization_id}
            />

            <div className="flex flex-1 flex-col overflow-hidden">
                <Topbar />

                <main className="flex-1 overflow-y-auto p-6">{children}</main>
            </div>
        </div>
    );
}
