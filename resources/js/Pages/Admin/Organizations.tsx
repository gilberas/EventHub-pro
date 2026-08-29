import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { router } from '@inertiajs/react';

interface OrgItem {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    users_count: number;
    created_at: string;
    subscription_plan?: string;
}

interface Props extends PageProps {
    organizations: { data: OrgItem[] };
}

export default function AdminOrganizations({ organizations }: Props) {
    const toggleStatus = (org: OrgItem) => {
        if (
            confirm(
                `Are you sure you want to ${org.is_active ? 'suspend' : 'activate'} "${org.name}"?`,
            )
        ) {
            router.post(route('admin.organizations.toggle-status', org.id));
        }
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Organizations</h1>
                <div className="overflow-hidden rounded-lg border bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    Name
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Slug
                                </th>
                                <th className="px-4 py-3 text-center font-medium">
                                    Users
                                </th>
                                <th className="px-4 py-3 text-center font-medium">
                                    Plan
                                </th>
                                <th className="px-4 py-3 text-center font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-center font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {organizations.data.map((org) => (
                                <tr key={org.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-medium">
                                        {org.name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">
                                        {org.slug}
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        {org.users_count}
                                    </td>
                                    <td className="px-4 py-3 text-center capitalize">
                                        {org.subscription_plan ?? 'Free'}
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <span
                                            className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${org.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}
                                        >
                                            {org.is_active
                                                ? 'Active'
                                                : 'Suspended'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <button
                                            onClick={() => toggleStatus(org)}
                                            className={`rounded px-3 py-1 text-xs font-medium ${org.is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100'}`}
                                        >
                                            {org.is_active
                                                ? 'Suspend'
                                                : 'Activate'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </DashboardLayout>
    );
}
