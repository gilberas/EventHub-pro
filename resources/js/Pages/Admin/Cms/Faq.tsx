import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface FaqItemData {
    id: number;
    question: string;
    answer: string;
    category: string | null;
    sort_order: number;
    is_published: boolean;
}

interface Props extends PageProps {
    items: { data: FaqItemData[] };
}

export default function AdminFaq({ items }: Props) {
    const [editing, setEditing] = useState<Partial<FaqItemData> | null>(null);

    const save = () => {
        if (!editing) return;
        if (editing.id) {
            router.put(route('admin.cms.faq.update', editing.id), editing);
        } else {
            router.post(route('admin.cms.faq'), editing);
        }
        setEditing(null);
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-5xl px-4 py-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">FAQ</h1>
                    <button
                        onClick={() =>
                            setEditing({
                                question: '',
                                answer: '',
                                sort_order: 0,
                            })
                        }
                        className="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                    >
                        Add Item
                    </button>
                </div>

                {editing && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                        <div className="mx-4 w-full max-w-lg rounded-lg bg-white p-6">
                            <h2 className="mb-4 text-lg font-bold">
                                {editing.id ? 'Edit FAQ' : 'New FAQ'}
                            </h2>
                            <div className="space-y-3">
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Question"
                                    value={editing.question || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            question: e.target.value,
                                        })
                                    }
                                />
                                <textarea
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    rows={4}
                                    placeholder="Answer"
                                    value={editing.answer || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            answer: e.target.value,
                                        })
                                    }
                                />
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Category (optional)"
                                    value={editing.category || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            category: e.target.value,
                                        })
                                    }
                                />
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
                    {items.data.map((item) => (
                        <div
                            key={item.id}
                            className="rounded-lg border bg-white p-4"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="font-medium">
                                        {item.question}
                                    </p>
                                    <p className="mt-1 text-sm text-gray-600">
                                        {item.answer}
                                    </p>
                                    <div className="mt-1 flex gap-2">
                                        {item.category && (
                                            <span className="text-xs text-gray-400">
                                                {item.category}
                                            </span>
                                        )}
                                        <span className="text-xs text-gray-400">
                                            Order: {item.sort_order}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => setEditing(item)}
                                        className="text-xs text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => {
                                            if (confirm('Delete?'))
                                                router.delete(
                                                    route(
                                                        'admin.cms.faq.destroy',
                                                        item.id,
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
