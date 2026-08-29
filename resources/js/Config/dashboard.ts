import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    BarChart3,
    Bookmark,
    Building2,
    Calendar,
    CreditCard,
    DollarSign,
    FileText,
    Globe,
    HardDrive,
    HelpCircle,
    LayoutDashboard,
    Lock,
    MapPin,
    MessageSquare,
    Palette,
    Receipt,
    RefreshCw,
    ScrollText,
    Settings,
    Shield,
    ShoppingCart,
    TicketCheck,
    Tickets,
    UserCheck,
    UserCog,
    Users,
    Wallet,
} from 'lucide-react';

export interface NavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    badge?: string;
    disabled?: boolean;
}

export interface NavGroup {
    label: string;
    items: NavItem[];
}

export interface RoleConfig {
    label: string;
    isOrgScoped: boolean;
    navGroups: NavGroup[];
}

export type RoleName =
    | 'Customer'
    | 'TicketScanner'
    | 'SupportAgent'
    | 'EventManager'
    | 'FinanceManager'
    | 'OrganizationAdmin'
    | 'OrganizationOwner'
    | 'PlatformAdmin'
    | 'SuperAdministrator';

export const roleConfigs: Record<RoleName, RoleConfig> = {
    Customer: {
        label: 'Customer',
        isOrgScoped: false,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'My Events',
                items: [
                    {
                        label: 'Upcoming Events',
                        href: '/events/search',
                        icon: Calendar,
                    },
                    {
                        label: 'Past Events',
                        href: '#',
                        icon: Calendar,
                        disabled: true,
                    },
                    {
                        label: 'Bookmarks',
                        href: '/favorites',
                        icon: Bookmark,
                    },
                ],
            },
            {
                label: 'Orders',
                items: [
                    {
                        label: 'My Orders',
                        href: '/bookings',
                        icon: ShoppingCart,
                    },
                    {
                        label: 'Order History',
                        href: '/payments/history',
                        icon: ScrollText,
                    },
                ],
            },
            {
                label: 'Support',
                items: [
                    {
                        label: 'My Tickets',
                        href: '/tickets',
                        icon: Tickets,
                    },
                    {
                        label: 'Help Center',
                        href: '#',
                        icon: HelpCircle,
                        disabled: true,
                    },
                ],
            },
        ],
    },

    TicketScanner: {
        label: 'Ticket Scanner',
        isOrgScoped: true,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Scanning',
                items: [
                    {
                        label: 'Scan Queue',
                        href: '/scanner',
                        icon: TicketCheck,
                    },
                    {
                        label: 'Scan History',
                        href: '#',
                        icon: ScrollText,
                        disabled: true,
                    },
                ],
            },
            {
                label: 'Events',
                items: [
                    {
                        label: 'Assigned Events',
                        href: '#',
                        icon: Calendar,
                        disabled: true,
                    },
                    {
                        label: 'Event Check-in',
                        href: '/scanner',
                        icon: UserCheck,
                    },
                ],
            },
        ],
    },

    SupportAgent: {
        label: 'Support Agent',
        isOrgScoped: true,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Support',
                items: [
                    {
                        label: 'Open Tickets',
                        href: '#',
                        icon: Tickets,
                        badge: 'New',
                        disabled: true,
                    },
                    {
                        label: 'Resolved Tickets',
                        href: '#',
                        icon: Tickets,
                        disabled: true,
                    },
                    {
                        label: 'Live Chat',
                        href: '#',
                        icon: MessageSquare,
                        disabled: true,
                    },
                ],
            },
            {
                label: 'Resources',
                items: [
                    {
                        label: 'Knowledge Base',
                        href: '#',
                        icon: FileText,
                        disabled: true,
                    },
                    {
                        label: 'FAQ Templates',
                        href: '#',
                        icon: HelpCircle,
                        disabled: true,
                    },
                ],
            },
        ],
    },

    EventManager: {
        label: 'Event Manager',
        isOrgScoped: true,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Events',
                items: [
                    { label: 'My Events', href: '/org/events', icon: Calendar },
                    {
                        label: 'Create Event',
                        href: '/org/events/create',
                        icon: Calendar,
                    },
                    {
                        label: 'Event Schedule',
                        href: '#',
                        icon: Calendar,
                        disabled: true,
                    },
                ],
            },
            {
                label: 'Management',
                items: [
                    { label: 'Venues', href: '/venues', icon: MapPin },
                    {
                        label: 'Attendees',
                        href: '#',
                        icon: Users,
                        disabled: true,
                    },
                    {
                        label: 'Staff',
                        href: '/organizations/{orgId}/settings?tab=staff',
                        icon: UserCheck,
                    },
                ],
            },
        ],
    },

    FinanceManager: {
        label: 'Finance Manager',
        isOrgScoped: true,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Finance',
                items: [
                    {
                        label: 'Revenue',
                        href: '#',
                        icon: DollarSign,
                        disabled: true,
                    },
                    {
                        label: 'Payouts',
                        href: '#',
                        icon: Wallet,
                        disabled: true,
                    },
                    {
                        label: 'Refunds',
                        href: '/payments/pending-refunds',
                        icon: RefreshCw,
                    },
                ],
            },
            {
                label: 'Documents',
                items: [
                    {
                        label: 'Invoices',
                        href: '#',
                        icon: Receipt,
                        disabled: true,
                    },
                    { label: 'Reports', href: '/org/reports', icon: BarChart3 },
                ],
            },
        ],
    },

    OrganizationAdmin: {
        label: 'Organization Admin',
        isOrgScoped: true,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Events',
                items: [
                    {
                        label: 'All Events',
                        href: '/org/events',
                        icon: Calendar,
                    },
                    {
                        label: 'Event Types',
                        href: '#',
                        icon: Calendar,
                        disabled: true,
                    },
                    { label: 'Venues', href: '/venues', icon: MapPin },
                ],
            },
            {
                label: 'People',
                items: [
                    {
                        label: 'Users & Roles',
                        href: '/organizations/{orgId}/settings?tab=staff',
                        icon: Users,
                    },
                    {
                        label: 'Staff',
                        href: '/organizations/{orgId}/settings?tab=staff',
                        icon: UserCog,
                    },
                ],
            },
            {
                label: 'Finance',
                items: [
                    {
                        label: 'Revenue',
                        href: '/org/reports',
                        icon: DollarSign,
                    },
                    { label: 'Reports', href: '/org/reports', icon: BarChart3 },
                ],
            },
            {
                label: 'Settings',
                items: [
                    {
                        label: 'Organization Settings',
                        href: '/organizations/{orgId}/settings',
                        icon: Settings,
                    },
                ],
            },
        ],
    },

    OrganizationOwner: {
        label: 'Organization Owner',
        isOrgScoped: true,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Events',
                items: [
                    {
                        label: 'All Events',
                        href: '/org/events',
                        icon: Calendar,
                    },
                    {
                        label: 'Event Types',
                        href: '#',
                        icon: Calendar,
                        disabled: true,
                    },
                    { label: 'Venues', href: '/venues', icon: MapPin },
                ],
            },
            {
                label: 'People',
                items: [
                    {
                        label: 'Users & Roles',
                        href: '/organizations/{orgId}/settings?tab=staff',
                        icon: Users,
                    },
                    {
                        label: 'Staff',
                        href: '/organizations/{orgId}/settings?tab=staff',
                        icon: UserCog,
                    },
                ],
            },
            {
                label: 'Finance',
                items: [
                    {
                        label: 'Revenue',
                        href: '/org/reports',
                        icon: DollarSign,
                    },
                    {
                        label: 'Billing',
                        href: '#',
                        icon: CreditCard,
                        disabled: true,
                    },
                    {
                        label: 'Invoices',
                        href: '#',
                        icon: Receipt,
                        disabled: true,
                    },
                    { label: 'Reports', href: '/org/reports', icon: BarChart3 },
                ],
            },
            {
                label: 'Business',
                items: [
                    {
                        label: 'Subscriptions',
                        href: '#',
                        icon: CreditCard,
                        disabled: true,
                    },
                    {
                        label: 'Branding',
                        href: '#',
                        icon: Palette,
                        disabled: true,
                    },
                ],
            },
            {
                label: 'Settings',
                items: [
                    {
                        label: 'Organization Settings',
                        href: '/organizations/{orgId}/settings',
                        icon: Settings,
                    },
                ],
            },
        ],
    },

    PlatformAdmin: {
        label: 'Platform Admin',
        isOrgScoped: false,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Platform',
                items: [
                    {
                        label: 'Organizations',
                        href: '/admin/organizations',
                        icon: Building2,
                    },
                    {
                        label: 'Platform Users',
                        href: '/admin/users',
                        icon: Users,
                    },
                    {
                        label: 'Audit Log',
                        href: '/admin/audit-log',
                        icon: ScrollText,
                    },
                ],
            },
            {
                label: 'Configuration',
                items: [
                    {
                        label: 'System Config',
                        href: '#',
                        icon: Settings,
                        disabled: true,
                    },
                    {
                        label: 'Feature Flags',
                        href: '#',
                        icon: Shield,
                        disabled: true,
                    },
                ],
            },
            {
                label: 'Monitoring',
                items: [
                    {
                        label: 'System Health',
                        href: '/admin/system/health',
                        icon: Activity,
                    },
                    {
                        label: 'Usage Analytics',
                        href: '#',
                        icon: BarChart3,
                        disabled: true,
                    },
                ],
            },
        ],
    },

    SuperAdministrator: {
        label: 'Super Administrator',
        isOrgScoped: false,
        navGroups: [
            {
                label: 'Main',
                items: [
                    {
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: LayoutDashboard,
                    },
                ],
            },
            {
                label: 'Platform',
                items: [
                    {
                        label: 'Organizations',
                        href: '/admin/organizations',
                        icon: Building2,
                    },
                    { label: 'All Users', href: '/admin/users', icon: Users },
                    {
                        label: 'Audit Log',
                        href: '/admin/audit-log',
                        icon: ScrollText,
                    },
                ],
            },
            {
                label: 'Administration',
                items: [
                    {
                        label: 'Global Settings',
                        href: '#',
                        icon: Globe,
                        disabled: true,
                    },
                    {
                        label: 'Security',
                        href: '#',
                        icon: Lock,
                        disabled: true,
                    },
                    {
                        label: 'System Config',
                        href: '#',
                        icon: Settings,
                        disabled: true,
                    },
                ],
            },
            {
                label: 'Infrastructure',
                items: [
                    {
                        label: 'System Health',
                        href: '/admin/system/health',
                        icon: Activity,
                    },
                    {
                        label: 'Maintenance',
                        href: '#',
                        icon: HardDrive,
                        disabled: true,
                    },
                    {
                        label: 'Usage Analytics',
                        href: '#',
                        icon: BarChart3,
                        disabled: true,
                    },
                ],
            },
        ],
    },
};

export function getRoleConfig(roleName: string): RoleConfig | null {
    return roleConfigs[roleName as RoleName] ?? null;
}

export function getHighestRole(roles: { name: string }[]): string | null {
    const roleHierarchy: RoleName[] = [
        'SuperAdministrator',
        'PlatformAdmin',
        'OrganizationOwner',
        'OrganizationAdmin',
        'FinanceManager',
        'EventManager',
        'SupportAgent',
        'TicketScanner',
        'Customer',
    ];

    const userRoleNames = new Set(roles.map((r) => r.name));

    for (const role of roleHierarchy) {
        if (userRoleNames.has(role)) {
            return role;
        }
    }

    return null;
}
