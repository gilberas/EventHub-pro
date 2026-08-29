import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface Props extends PageProps {
    queue_size: number;
    cache_driver: string;
    queue_driver: string;
    maintenance_mode: boolean;
    php_version: string;
    laravel_version: string;
}

export default function SystemHealth({
    queue_size,
    cache_driver,
    queue_driver,
    maintenance_mode,
    php_version,
    laravel_version,
}: Props) {
    const [clearing, setClearing] = useState(false);

    const handleClearCache = () => {
        setClearing(true);
        router.post(
            route('admin.system.clear-cache'),
            {},
            {
                onFinish: () => setClearing(false),
            },
        );
    };

    const handleToggleMaintenance = () => {
        router.post(route('admin.system.toggle-maintenance'));
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-4xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">System Health</h1>

                <div className="mb-6 grid gap-4 sm:grid-cols-3">
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">PHP Version</p>
                        <p className="text-xl font-bold">{php_version}</p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Laravel</p>
                        <p className="text-xl font-bold">{laravel_version}</p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Queue Size</p>
                        <p className="text-xl font-bold">{queue_size}</p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Cache Driver</p>
                        <p className="text-xl font-bold">{cache_driver}</p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Queue Driver</p>
                        <p className="text-xl font-bold">{queue_driver}</p>
                    </div>
                    <div className="rounded-lg border bg-white p-4">
                        <p className="text-sm text-gray-500">Maintenance</p>
                        <p
                            className={`text-xl font-bold ${maintenance_mode ? 'text-red-600' : 'text-green-600'}`}
                        >
                            {maintenance_mode ? 'ON' : 'OFF'}
                        </p>
                    </div>
                </div>

                <div className="rounded-lg border bg-white p-6">
                    <h2 className="mb-4 text-lg font-bold">Actions</h2>
                    <div className="flex flex-wrap gap-3">
                        <button
                            onClick={handleClearCache}
                            disabled={clearing}
                            className="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {clearing ? 'Clearing...' : 'Clear Cache'}
                        </button>
                        <button
                            onClick={handleToggleMaintenance}
                            className={`rounded px-4 py-2 text-sm ${maintenance_mode ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-yellow-600 text-white hover:bg-yellow-700'}`}
                        >
                            {maintenance_mode
                                ? 'Bring Online'
                                : 'Enable Maintenance'}
                        </button>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
