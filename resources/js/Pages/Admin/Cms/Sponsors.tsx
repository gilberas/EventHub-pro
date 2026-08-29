import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface SponsorData {
    id: number;
    name: string;
    website_url: string | null;
    tier: string;
    sort_order: number;
    is_active: boolean;
}

interface Props extends PageProps {
    sponsors: SponsorData[];
}

export default function AdminSponsors({ sponsors }: Props) {
    const [editing, setEditing] = useState<Partial<SponsorData> | null>(null);

    const save = () => {
        if (!editing) return;
        if (editing.id) {
            router.put(route('admin.cms.sponsors.update', editing.id), editing);
        } else {
            router.post(route('admin.cms.sponsors'), editing);
        }
        setEditing(null);
    };

    const tierColors: Record<string, string> = {
        bronze: 'bg-orange-100 text-orange-700',
        silver: 'bg-gray-100 text-gray-700',
        gold: 'bg-yellow-100 text-yellow-700',
        platinum: 'bg-purple-100 text-purple-700',
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-5xl px-4 py-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Sponsors</h1>
                    <button
                        onClick={() =>
                            setEditing({
                                name: '',
                                tier: 'bronze',
                                sort_order: 0,
                                is_active: true,
                            })
                        }
                        className="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                    >
                        Add Sponsor
                    </button>
                </div>

                {editing && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                        <div className="mx-4 w-full max-w-lg rounded-lg bg-white p-6">
                            <h2 className="mb-4 text-lg font-bold">
                                {editing.id ? 'Edit Sponsor' : 'New Sponsor'}
                            </h2>
                            <div className="space-y-3">
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Name"
                                    value={editing.name || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            name: e.target.value,
                                        })
                                    }
                                />
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Website URL"
                                    value={editing.website_url || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            website_url: e.target.value,
                                        })
                                    }
                                />
                                <select
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    value={editing.tier || 'bronze'}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            tier: e.target.value,
                                        })
                                    }
                                >
                                    <option value="bronze">Bronze</option>
                                    <option value="silver">Silver</option>
                                    <option value="gold">Gold</option>
                                    <option value="platinum">Platinum</option>
                                </select>
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    type="number"
                                    placeholder="Sort order"
                                    value={editing.sort_order ?? 0}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            sort_order:
                                                parseInt(e.target.value) || 0,
                                        })
                                    }
                                />
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={editing.is_active ?? true}
                                        onChange={(e) =>
                                            setEditing({
                                                ...editing,
                                                is_active: e.target.checked,
                                            })
                                        }
                                    />
                                    Active
                                </label>
                            </div>
                            <div className="mt-4 flex gap-2">
                                <button
                                    onClick={save}
                                    className="rounded bg-blue-600 px-4 py-2 text-sm text-white"
                                >
                                    Save
                                </button>
                                <button
                                    onClick={() => setEditing(null)}
                                    className="rounded bg-gray-100 px-4 py-2 text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                <div className="space-y-3">
                    {sponsors.map((sponsor) => (
                        <div
                            key={sponsor.id}
                            className="rounded-lg border bg-white p-4"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="font-medium">
                                        {sponsor.name}
                                    </p>
                                    {sponsor.website_url && (
                                        <p className="text-xs text-blue-500">
                                            {sponsor.website_url}
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${tierColors[sponsor.tier] || 'bg-gray-100'}`}
                                    >
                                        {sponsor.tier}
                                    </span>
                                    <button
                                        onClick={() => setEditing(sponsor)}
                                        className="text-xs text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => {
                                            if (confirm('Delete?'))
                                                router.delete(
                                                    route(
                                                        'admin.cms.sponsors.destroy',
                                                        sponsor.id,
                                                    ),
                                                );
                                        }}
                                        className="text-xs text-red-600 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
