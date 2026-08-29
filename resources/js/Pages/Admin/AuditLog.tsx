import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';

interface LogEntry {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
}

interface Props extends PageProps {
    logs: { data: LogEntry[] };
}

export default function AuditLog({ logs }: Props) {
    return (
        <DashboardLayout>
            <div className="mx-auto max-w-6xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Audit Log</h1>
                <div className="space-y-3">
                    {logs.data.map((entry) => (
                        <div
                            key={entry.id}
                            className="rounded-lg border bg-white p-4 text-sm"
                        >
                            <div className="mb-2 flex items-start justify-between">
                                <div>
                                    <span className="mr-2 inline-block rounded bg-gray-100 px-2 py-0.5 text-xs font-medium capitalize">
                                        {entry.event}
                                    </span>
                                    <span className="text-xs text-gray-500">
                                        {entry.auditable_type}
                                    </span>
                                    <span className="ml-2 text-xs text-gray-400">
                                        ID: {entry.auditable_id}
                                    </span>
                                </div>
                                <div className="text-right text-xs text-gray-400">
                                    <div>
                                        {entry.user?.name ?? 'System'} (
                                        {entry.user?.email ?? 'N/A'})
                                    </div>
                                    <div>
                                        {new Date(
                                            entry.created_at,
                                        ).toLocaleString()}
                                    </div>
                                </div>
                            </div>
                            {(entry.old_values || entry.new_values) && (
                                <div className="mt-2 grid grid-cols-2 gap-2 rounded bg-gray-50 p-2 text-xs">
                                    {entry.old_values && (
                                        <div>
                                            <span className="font-semibold text-red-600">
                                                Old:
                                            </span>
                                            <pre className="mt-1 whitespace-pre-wrap">
                                                {JSON.stringify(
                                                    entry.old_values,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </div>
                                    )}
                                    {entry.new_values && (
                                        <div>
                                            <span className="font-semibold text-green-600">
                                                New:
                                            </span>
                                            <pre className="mt-1 whitespace-pre-wrap">
                                                {JSON.stringify(
                                                    entry.new_values,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
