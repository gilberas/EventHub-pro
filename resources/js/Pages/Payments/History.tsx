import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface PaymentTransactionItem {
    id: number;
    gateway: string;
    transaction_id: string | null;
    type: string;
    amount: number;
    currency: string;
    status: string;
    created_at: string;
}

interface InvoiceItem {
    id: number;
    number: string;
    status: string;
    total: number;
    currency: string;
    pdf_path: string | null;
}

interface BookingItem {
    id: number;
    reference: string;
    status: string;
    total: number;
    currency: string;
    created_at: string;
    event_session: { event: { title: string } } | null;
    transactions: PaymentTransactionItem[];
    invoices: InvoiceItem[];
}

interface Props extends PageProps {
    bookings: { data: BookingItem[] };
}

export default function PaymentHistory({ bookings }: Props) {
    const user = usePage().props.auth.user;

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-4xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">Payment History</h1>

                {bookings.data.length === 0 ? (
                    <p className="text-gray-500">No payment history found.</p>
                ) : (
                    <div className="space-y-4">
                        {bookings.data.map((booking) => (
                            <div
                                key={booking.id}
                                className="rounded-lg border bg-white p-4 shadow-sm"
                            >
                                <div className="mb-3 flex items-start justify-between">
                                    <div>
                                        <Link
                                            href={route(
                                                'bookings.show',
                                                booking.reference,
                                            )}
                                            className="font-semibold text-blue-600 hover:underline"
                                        >
                                            {booking.reference}
                                        </Link>
                                        <p className="text-sm text-gray-600">
                                            {booking.event_session?.event
                                                ?.title ?? 'Unknown Event'}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <span
                                            className={`inline-block rounded-full px-2 py-1 text-xs font-semibold ${
                                                booking.status === 'confirmed'
                                                    ? 'bg-green-100 text-green-800'
                                                    : booking.status ===
                                                        'refunded'
                                                      ? 'bg-red-100 text-red-800'
                                                      : booking.status ===
                                                          'partially_refunded'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {booking.status.replace('_', ' ')}
                                        </span>
                                        <p className="mt-1 text-sm font-medium">
                                            ${Number(booking.total).toFixed(2)}
                                        </p>
                                    </div>
                                </div>

                                {booking.transactions.length > 0 && (
                                    <div className="mt-2 border-t pt-2">
                                        <h4 className="mb-1 text-xs font-semibold text-gray-500 uppercase">
                                            Transactions
                                        </h4>
                                        {booking.transactions.map((tx) => (
                                            <div
                                                key={tx.id}
                                                className="flex justify-between py-1 text-sm"
                                            >
                                                <span className="text-gray-600">
                                                    {tx.type} ({tx.gateway})
                                                </span>
                                                <span
                                                    className={
                                                        tx.status ===
                                                        'succeeded'
                                                            ? 'text-green-600'
                                                            : 'text-red-600'
                                                    }
                                                >
                                                    $
                                                    {Number(tx.amount).toFixed(
                                                        2,
                                                    )}{' '}
                                                    — {tx.status}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {booking.invoices.length > 0 && (
                                    <div className="mt-2 border-t pt-2">
                                        <h4 className="mb-1 text-xs font-semibold text-gray-500 uppercase">
                                            Invoices
                                        </h4>
                                        {booking.invoices.map((inv) => (
                                            <div
                                                key={inv.id}
                                                className="flex justify-between py-1 text-sm"
                                            >
                                                <span className="text-gray-600">
                                                    {inv.number}
                                                </span>
                                                <span className="text-gray-800">
                                                    $
                                                    {Number(inv.total).toFixed(
                                                        2,
                                                    )}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="mt-2 border-t pt-2 text-xs text-gray-400">
                                    {new Date(
                                        booking.created_at,
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
