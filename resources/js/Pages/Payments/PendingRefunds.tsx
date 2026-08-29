import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface RefundRequestItem {
    id: number;
    amount: number;
    reason: string;
    customer_notes: string | null;
    status: string;
    created_at: string;
    booking: {
        id: number;
        reference: string;
        total: number;
        event_session: { event: { title: string } } | null;
    } | null;
    requested_by: { id: number; name: string; email: string } | null;
}

interface Props extends PageProps {
    refunds: { data: RefundRequestItem[] };
}

export default function PendingRefunds({ refunds }: Props) {
    const [reviewNotes, setReviewNotes] = useState<Record<number, string>>({});

    const handleApprove = (id: number) => {
        router.post(route('payments.refunds.approve', id), {
            notes: reviewNotes[id] || '',
        });
    };

    const handleReject = (id: number) => {
        router.post(route('payments.refunds.reject', id), {
            notes: reviewNotes[id] || '',
        });
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-4xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">
                    Pending Refund Requests
                </h1>

                {refunds.data.length === 0 ? (
                    <p className="text-gray-500">No pending refund requests.</p>
                ) : (
                    <div className="space-y-4">
                        {refunds.data.map((refund) => (
                            <div
                                key={refund.id}
                                className="rounded-lg border bg-white p-4 shadow-sm"
                            >
                                <div className="mb-2 flex items-start justify-between">
                                    <div>
                                        <p className="font-semibold">
                                            {refund.booking?.reference ??
                                                'Unknown Booking'}
                                        </p>
                                        <p className="text-sm text-gray-600">
                                            {refund.booking?.event_session
                                                ?.event?.title ??
                                                'Unknown Event'}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <span className="inline-block rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">
                                            {refund.status}
                                        </span>
                                        <p className="mt-1 text-sm font-medium">
                                            ${Number(refund.amount).toFixed(2)}
                                        </p>
                                    </div>
                                </div>

                                <div className="mb-3 text-sm text-gray-700">
                                    <p>
                                        <strong>Requested by:</strong>{' '}
                                        {refund.requested_by?.name ?? 'Unknown'}{' '}
                                        ({refund.requested_by?.email ?? ''})
                                    </p>
                                    <p>
                                        <strong>Reason:</strong> {refund.reason}
                                    </p>
                                    {refund.customer_notes && (
                                        <p>
                                            <strong>Notes:</strong>{' '}
                                            {refund.customer_notes}
                                        </p>
                                    )}
                                </div>

                                <div className="border-t pt-3">
                                    <textarea
                                        className="mb-2 w-full rounded border p-2 text-sm"
                                        rows={2}
                                        placeholder="Review notes (optional)..."
                                        value={reviewNotes[refund.id] || ''}
                                        onChange={(e) =>
                                            setReviewNotes((prev) => ({
                                                ...prev,
                                                [refund.id]: e.target.value,
                                            }))
                                        }
                                    />
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() =>
                                                handleApprove(refund.id)
                                            }
                                            className="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
                                        >
                                            Approve & Refund
                                        </button>
                                        <button
                                            onClick={() =>
                                                handleReject(refund.id)
                                            }
                                            className="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </div>

                                <div className="mt-2 text-xs text-gray-400">
                                    {new Date(
                                        refund.created_at,
                                    ).toLocaleDateString()}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </DashboardLayout>
    );
}
