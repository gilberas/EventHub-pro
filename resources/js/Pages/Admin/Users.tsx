import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';

interface UserItem {
    id: number;
    name: string;
    email: string;
    created_at: string;
    roles: { id: number; name: string; organization_id: number | null }[];
}

interface Props extends PageProps {
    users: { data: UserItem[] };
}

export default function AdminUsers({ users }: Props) {
    return (
        <DashboardLayout>
            <div className="mx-auto max-w-5xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Users</h1>
                <div className="overflow-hidden rounded-lg border bg-white">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    Name
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Email
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Roles
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Joined
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {users.data.map((user) => (
                                <tr key={user.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-medium">
                                        {user.name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">
                                        {user.email}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles.map((role) => (
                                                <span
                                                    key={role.id}
                                                    className="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
                                                >
                                                    {role.name}
                                                </span>
                                            ))}
                                            {user.roles.length === 0 && (
                                                <span className="text-xs text-gray-400">
                                                    No roles
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">
                                        {new Date(
                                            user.created_at,
                                        ).toLocaleDateString()}
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
