import type { NavGroup } from '@/Config/dashboard';
import { cn } from '@/Lib/utils';
import { Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    LogOut,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';

interface SidebarProps {
    navGroups: NavGroup[];
    currentPath: string;
    onLogout: () => void;
    currentOrgId?: number | null;
}

export default function Sidebar({
    navGroups,
    currentPath,
    onLogout,
    currentOrgId,
}: SidebarProps) {
    const resolveHref = (href: string) => {
        if (currentOrgId) {
            return href.replace('{orgId}', String(currentOrgId));
        }
        return href;
    };
    const [collapsed, setCollapsed] = useState(false);

    return (
        <aside
            className={cn(
                'border-border bg-card flex h-screen flex-col border-r transition-all duration-200',
                collapsed ? 'w-16' : 'w-64',
            )}
        >
            <div className="border-border flex h-14 items-center border-b px-4">
                <Link
                    href="/dashboard"
                    className="text-foreground flex items-center gap-2 font-semibold"
                >
                    {collapsed ? (
                        <span className="text-lg">EH</span>
                    ) : (
                        <>
                            <span className="bg-primary text-primary-foreground flex h-7 w-7 items-center justify-center rounded-md text-xs font-bold">
                                EH
                            </span>
                            <span className="text-sm">EventHub</span>
                        </>
                    )}
                </Link>
            </div>

            <nav className="flex-1 space-y-4 overflow-y-auto p-2">
                {navGroups.map((group) => (
                    <div key={group.label}>
                        {!collapsed && (
                            <p className="text-muted-foreground mb-1 px-2 text-xs font-medium tracking-wider uppercase">
                                {group.label}
                            </p>
                        )}
                        <ul className="space-y-0.5">
                            {group.items.map((item) => {
                                const Icon = item.icon as LucideIcon;
                                const resolvedHref = resolveHref(item.href);
                                const isActive = currentPath === resolvedHref;
                                return (
                                    <li key={item.label}>
                                        {item.disabled ? (
                                            <span
                                                className={cn(
                                                    'flex cursor-not-allowed items-center gap-3 rounded-md px-2 py-2 text-sm font-medium opacity-40',
                                                    !collapsed &&
                                                        'justify-between',
                                                )}
                                                title="Coming soon"
                                            >
                                                <span className="flex items-center gap-3">
                                                    <Icon className="h-5 w-5 shrink-0" />
                                                    {!collapsed && (
                                                        <span className="truncate">
                                                            {item.label}
                                                        </span>
                                                    )}
                                                </span>
                                                {!collapsed && (
                                                    <span className="text-muted-foreground text-[10px] tracking-wider uppercase">
                                                        Soon
                                                    </span>
                                                )}
                                            </span>
                                        ) : (
                                            <Link
                                                href={resolvedHref}
                                                className={cn(
                                                    'flex items-center gap-3 rounded-md px-2 py-2 text-sm font-medium transition-colors',
                                                    isActive
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                                )}
                                            >
                                                <Icon className="h-5 w-5 shrink-0" />
                                                {!collapsed && (
                                                    <span className="flex-1 truncate">
                                                        {item.label}
                                                    </span>
                                                )}
                                                {!collapsed && item.badge && (
                                                    <span className="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-xs font-medium">
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </Link>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </nav>

            <div className="border-border border-t p-2">
                <button
                    onClick={onLogout}
                    className="text-muted-foreground hover:bg-muted hover:text-foreground flex w-full items-center gap-3 rounded-md px-2 py-2 text-sm font-medium transition-colors"
                >
                    <LogOut className="h-5 w-5 shrink-0" />
                    {!collapsed && <span>Log Out</span>}
                </button>
            </div>

            <button
                onClick={() => setCollapsed(!collapsed)}
                className="border-border text-muted-foreground hover:text-foreground flex items-center justify-center border-t p-2"
            >
                {collapsed ? (
                    <ChevronRight className="h-4 w-4" />
                ) : (
                    <ChevronLeft className="h-4 w-4" />
                )}
            </button>
        </aside>
    );
}
