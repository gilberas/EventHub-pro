import WidgetCard from '@/Components/Dashboard/WidgetCard';
import DashboardLayout from '@/Layouts/DashboardLayout';
import type { Organization } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { DollarSign, Mail, Plus, UserCog, Users, X } from 'lucide-react';
import { useState } from 'react';

interface StaffMember {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface Invitation {
    id: number;
    email: string;
    role: string;
    created_at: string;
    accepted_at: string | null;
    expires_at: string;
}

interface Props {
    organization: Organization & {
        timezone: string;
        currency: string;
        billing_email: string | null;
        billing_address: string | null;
        refund_policy_days: number | null;
        refund_policy_percentage: number | null;
        subscription_plan: string;
        logo_url: string | null;
        media?: { id: number; original_url: string }[];
    };
    staff: StaffMember[];
    invitations: Invitation[];
    currentUserRole: string | null;
    canManageStaff: boolean;
}

export default function OrganizationSettings({
    organization,
    staff,
    invitations,
    currentUserRole,
    canManageStaff,
}: Props) {
    const [activeTab, setActiveTab] = useState<'profile' | 'staff' | 'billing'>(
        () => {
            const tab = new URLSearchParams(window.location.search).get('tab');

            return tab === 'staff' || tab === 'billing' ? tab : 'profile';
        },
    );

    const { data, setData, patch, processing, errors } = useForm({
        name: organization.name,
        slug: organization.slug,
        timezone: organization.timezone,
        currency: organization.currency,
        billing_email: organization.billing_email ?? '',
        billing_address: organization.billing_address ?? '',
        refund_policy_days: organization.refund_policy_days ?? '',
        refund_policy_percentage: organization.refund_policy_percentage ?? '',
    });

    const canEdit =
        currentUserRole === 'OrganizationOwner' ||
        currentUserRole === 'OrganizationAdmin';

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch(route('organizations.update', organization.id));
    }

    return (
        <DashboardLayout>
            <Head title="Organization Settings" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Organization Settings
                </h1>
                <p className="text-muted-foreground">
                    Manage your organization's profile, staff, and billing.
                </p>
            </div>

            <div className="border-border bg-card mb-6 flex gap-1 rounded-lg border p-1">
                {(['profile', 'staff', 'billing'] as const).map((tab) => (
                    <button
                        key={tab}
                        onClick={() => setActiveTab(tab)}
                        className={`flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                            activeTab === tab
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        {tab === 'profile' && 'Profile'}
                        {tab === 'staff' && 'Staff'}
                        {tab === 'billing' && 'Billing'}
                    </button>
                ))}
            </div>

            {activeTab === 'profile' && (
                <form onSubmit={handleSubmit} className="space-y-6">
                    <WidgetCard title="General Information">
                        <div className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">
                                    Organization Name
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    disabled={!canEdit}
                                    className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-red-500">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm font-medium">
                                    Slug
                                </label>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) =>
                                        setData('slug', e.target.value)
                                    }
                                    disabled={!canEdit}
                                    className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                />
                                {errors.slug && (
                                    <p className="mt-1 text-xs text-red-500">
                                        {errors.slug}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">
                                        Timezone
                                    </label>
                                    <select
                                        value={data.timezone}
                                        onChange={(e) =>
                                            setData('timezone', e.target.value)
                                        }
                                        disabled={!canEdit}
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                    >
                                        <option value="UTC">UTC</option>
                                        <option value="America/New_York">
                                            America/New_York
                                        </option>
                                        <option value="America/Chicago">
                                            America/Chicago
                                        </option>
                                        <option value="America/Denver">
                                            America/Denver
                                        </option>
                                        <option value="America/Los_Angeles">
                                            America/Los_Angeles
                                        </option>
                                        <option value="Europe/London">
                                            Europe/London
                                        </option>
                                        <option value="Europe/Paris">
                                            Europe/Paris
                                        </option>
                                        <option value="Asia/Tokyo">
                                            Asia/Tokyo
                                        </option>
                                        <option value="Australia/Sydney">
                                            Australia/Sydney
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-sm font-medium">
                                        Currency
                                    </label>
                                    <select
                                        value={data.currency}
                                        onChange={(e) =>
                                            setData('currency', e.target.value)
                                        }
                                        disabled={!canEdit}
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                    >
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="GBP">GBP (£)</option>
                                        <option value="JPY">JPY (¥)</option>
                                        <option value="AUD">AUD (A$)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </WidgetCard>

                    <WidgetCard title="Refund Policy">
                        <div className="space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Default refund settings for events created by
                                this organization.
                            </p>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">
                                        Refund Period (days)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.refund_policy_days}
                                        onChange={(e) =>
                                            setData(
                                                'refund_policy_days',
                                                e.target.value === ''
                                                    ? ''
                                                    : Number(e.target.value),
                                            )
                                        }
                                        disabled={!canEdit}
                                        min={0}
                                        max={365}
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                    />
                                    {errors.refund_policy_days && (
                                        <p className="mt-1 text-xs text-red-500">
                                            {errors.refund_policy_days}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="text-sm font-medium">
                                        Refund Percentage (%)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.refund_policy_percentage}
                                        onChange={(e) =>
                                            setData(
                                                'refund_policy_percentage',
                                                e.target.value === ''
                                                    ? ''
                                                    : Number(e.target.value),
                                            )
                                        }
                                        disabled={!canEdit}
                                        min={0}
                                        max={100}
                                        step="0.01"
                                        className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                    />
                                    {errors.refund_policy_percentage && (
                                        <p className="mt-1 text-xs text-red-500">
                                            {errors.refund_policy_percentage}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </WidgetCard>

                    {canEdit && (
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    )}
                </form>
            )}

            {activeTab === 'staff' && (
                <div className="space-y-6">
                    {canManageStaff && (
                        <InviteStaffForm organizationId={organization.id} />
                    )}

                    {invitations.filter((i) => !i.accepted_at).length > 0 && (
                        <WidgetCard
                            title="Pending Invitations"
                            icon={<Mail className="h-4 w-4" />}
                        >
                            <div className="space-y-2">
                                {invitations
                                    .filter((i) => !i.accepted_at)
                                    .map((inv) => (
                                        <div
                                            key={inv.id}
                                            className="border-border flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <div className="flex items-center gap-3">
                                                <Mail className="text-muted-foreground h-4 w-4" />
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {inv.email}
                                                    </p>
                                                    <p className="text-muted-foreground text-xs">
                                                        Role: {inv.role}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs text-yellow-500">
                                                    Pending
                                                </span>
                                                {canManageStaff && (
                                                    <button
                                                        onClick={() =>
                                                            router.delete(
                                                                route(
                                                                    'organizations.invitations.cancel',
                                                                    inv.id,
                                                                ),
                                                            )
                                                        }
                                                        className="text-muted-foreground rounded-md p-1 hover:text-red-500"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </WidgetCard>
                    )}

                    <WidgetCard
                        title="Staff Members"
                        icon={<Users className="h-4 w-4" />}
                    >
                        <div className="space-y-2">
                            {staff.length === 0 && (
                                <p className="text-muted-foreground text-sm">
                                    No staff members yet.
                                </p>
                            )}
                            {staff.map((member) => (
                                <StaffRow
                                    key={member.id}
                                    member={member}
                                    canManage={canManageStaff}
                                    organizationId={organization.id}
                                />
                            ))}
                        </div>
                    </WidgetCard>
                </div>
            )}

            {activeTab === 'billing' && (
                <div className="space-y-6">
                    <WidgetCard
                        title="Subscription"
                        icon={<DollarSign className="h-4 w-4" />}
                    >
                        <div className="space-y-3">
                            <div className="border-border flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <p className="text-sm font-medium">
                                        Current Plan
                                    </p>
                                    <p className="text-muted-foreground text-xs capitalize">
                                        {organization.subscription_plan}
                                    </p>
                                </div>
                                <span className="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-xs font-medium">
                                    Active
                                </span>
                            </div>
                            <div>
                                <label className="text-sm font-medium">
                                    Billing Email
                                </label>
                                <input
                                    type="email"
                                    value={data.billing_email}
                                    onChange={(e) =>
                                        setData('billing_email', e.target.value)
                                    }
                                    disabled={!canEdit}
                                    className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                />
                            </div>
                            <div>
                                <label className="text-sm font-medium">
                                    Billing Address
                                </label>
                                <textarea
                                    value={data.billing_address}
                                    onChange={(e) =>
                                        setData(
                                            'billing_address',
                                            e.target.value,
                                        )
                                    }
                                    disabled={!canEdit}
                                    rows={3}
                                    className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-50"
                                />
                            </div>
                            {canEdit && (
                                <button
                                    onClick={handleSubmit}
                                    disabled={processing}
                                    className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                                >
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </button>
                            )}
                        </div>
                    </WidgetCard>
                </div>
            )}
        </DashboardLayout>
    );
}

function InviteStaffForm({ organizationId }: { organizationId: number }) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: 'SupportAgent',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('organizations.invitations.store', organizationId), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    return (
        <WidgetCard title="Invite Staff" icon={<UserCog className="h-4 w-4" />}>
            {!showForm ? (
                <button
                    onClick={() => setShowForm(true)}
                    className="border-border hover:bg-muted flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium"
                >
                    <Plus className="h-4 w-4" />
                    Send Invitation
                </button>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-3">
                    <div>
                        <label className="text-sm font-medium">
                            Email Address
                        </label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="colleague@example.com"
                        />
                        {errors.email && (
                            <p className="mt-1 text-xs text-red-500">
                                {errors.email}
                            </p>
                        )}
                    </div>
                    <div>
                        <label className="text-sm font-medium">Role</label>
                        <select
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
                        >
                            <option value="OrganizationAdmin">
                                Organization Admin
                            </option>
                            <option value="EventManager">Event Manager</option>
                            <option value="FinanceManager">
                                Finance Manager
                            </option>
                            <option value="SupportAgent">Support Agent</option>
                            <option value="TicketScanner">
                                Ticket Scanner
                            </option>
                        </select>
                        {errors.role && (
                            <p className="mt-1 text-xs text-red-500">
                                {errors.role}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-3 py-2 text-sm font-medium disabled:opacity-50"
                        >
                            {processing ? 'Sending...' : 'Send Invitation'}
                        </button>
                        <button
                            type="button"
                            onClick={() => setShowForm(false)}
                            className="border-border hover:bg-muted rounded-md border px-3 py-2 text-sm font-medium"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}
        </WidgetCard>
    );
}

function StaffRow({
    member,
    canManage,
    organizationId,
}: {
    member: StaffMember;
    canManage: boolean;
    organizationId: number;
}) {
    const [editing, setEditing] = useState(false);
    const { data, setData, put, processing } = useForm({
        role: member.role,
    });

    function handleRoleUpdate() {
        put(route('organizations.staff.role', [organizationId, member.id]), {
            onSuccess: () => setEditing(false),
        });
    }

    return (
        <div className="border-border flex items-center justify-between rounded-lg border p-3">
            <div className="flex items-center gap-3">
                <span className="bg-primary/10 text-primary flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium">
                    {member.name.charAt(0)}
                </span>
                <div>
                    <p className="text-sm font-medium">{member.name}</p>
                    <p className="text-muted-foreground text-xs">
                        {member.email}
                    </p>
                </div>
            </div>
            <div className="flex items-center gap-2">
                {editing ? (
                    <>
                        <select
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="border-border bg-background rounded-md border px-2 py-1 text-xs"
                        >
                            <option value="OrganizationAdmin">
                                Organization Admin
                            </option>
                            <option value="EventManager">Event Manager</option>
                            <option value="FinanceManager">
                                Finance Manager
                            </option>
                            <option value="SupportAgent">Support Agent</option>
                            <option value="TicketScanner">
                                Ticket Scanner
                            </option>
                        </select>
                        <button
                            onClick={handleRoleUpdate}
                            disabled={processing}
                            className="bg-primary text-primary-foreground rounded-md px-2 py-1 text-xs font-medium"
                        >
                            Save
                        </button>
                        <button
                            onClick={() => setEditing(false)}
                            className="border-border rounded-md border px-2 py-1 text-xs"
                        >
                            Cancel
                        </button>
                    </>
                ) : (
                    <>
                        <span className="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-xs font-medium">
                            {member.role}
                        </span>
                        {canManage && (
                            <>
                                <button
                                    onClick={() => setEditing(true)}
                                    className="text-muted-foreground hover:text-foreground rounded-md p-1"
                                >
                                    <UserCog className="h-4 w-4" />
                                </button>
                                <button
                                    onClick={() =>
                                        router.delete(
                                            route(
                                                'organizations.staff.remove',
                                                [organizationId, member.id],
                                            ),
                                        )
                                    }
                                    className="text-muted-foreground rounded-md p-1 hover:text-red-500"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}
