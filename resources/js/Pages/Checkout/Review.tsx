import PublicLayout from '@/Layouts/PublicLayout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, CreditCard, ShoppingCart } from 'lucide-react';

interface SeatInfo {
    id: number;
    seat_number: number;
    row: { label: string; section?: { name: string } } | null;
}

interface TicketTypeInfo {
    id: number;
    name: string;
    price: number;
}

interface HoldItem {
    id: number;
    ticket_type_id: number;
    seat_id: number | null;
    quantity: number;
    ticketType: TicketTypeInfo;
    seat: SeatInfo | null;
}

interface Props {
    holds: HoldItem[];
    expiresAt: string | null;
    summary: {
        subtotal: number;
        fees: number;
        total: number;
    };
    gateways: string[];
    defaultGateway: string;
}

export default function CheckoutReview({
    holds,
    expiresAt,
    summary,
    gateways,
    defaultGateway,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        hold_ids: holds.map((h) => h.id),
        gateway: defaultGateway ?? 'mock',
        coupon_code: '',
        payment: {} as Record<string, string>,
    });

    const isMock = data.gateway === 'mock';

    function handleCheckout() {
        post(route('checkout.pay'));
    }

    return (
        <PublicLayout>
            <Head title="Review Your Order" />

            <div className="mx-auto max-w-3xl px-4 py-8">
                <a
                    href="/"
                    className="text-muted-foreground hover:text-foreground mb-4 flex items-center gap-1 text-sm"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Events
                </a>

                <h1 className="mb-2 text-2xl font-bold tracking-tight">
                    Review Your Order
                </h1>

                {expiresAt && (
                    <p className="mb-6 flex items-center gap-1 text-sm text-yellow-600">
                        <Clock className="h-4 w-4" />
                        Items are held until{' '}
                        {new Date(expiresAt).toLocaleTimeString()}
                    </p>
                )}

                {holds.length === 0 ? (
                    <div className="border-border text-muted-foreground rounded-lg border-2 border-dashed p-12 text-center">
                        <ShoppingCart className="mx-auto mb-2 h-8 w-8" />
                        <p>Your cart is empty.</p>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {errors.checkout && (
                            <div className="rounded-lg bg-red-50 p-3 text-sm text-red-600">
                                {errors.checkout}
                            </div>
                        )}

                        <div className="space-y-3">
                            {holds.map((hold) => (
                                <div
                                    key={hold.id}
                                    className="border-border bg-card rounded-lg border p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium">
                                                {hold.ticketType?.name ??
                                                    'Ticket'}
                                            </p>
                                            {hold.seat && (
                                                <p className="text-muted-foreground text-sm">
                                                    Seat {hold.seat.row?.label}
                                                    {hold.seat.seat_number}
                                                    {hold.seat.row?.section
                                                        ?.name &&
                                                        ` — ${hold.seat.row.section.name}`}
                                                </p>
                                            )}
                                            <p className="text-muted-foreground text-sm">
                                                Qty: {hold.quantity}
                                            </p>
                                        </div>
                                        <p className="font-semibold">
                                            $
                                            {(
                                                (hold.ticketType?.price ?? 0) *
                                                hold.quantity
                                            ).toFixed(2)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="border-border bg-card rounded-lg border p-4">
                            <div className="space-y-1 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span>${summary.subtotal.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Fees (5%)
                                    </span>
                                    <span>${summary.fees.toFixed(2)}</span>
                                </div>
                                <div className="border-border flex justify-between border-t pt-2 text-base font-bold">
                                    <span>Total</span>
                                    <span>${summary.total.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>

                        <div className="border-border bg-card rounded-lg border p-4">
                            <label className="mb-2 block text-sm font-medium">
                                Payment Method
                            </label>
                            <div className="space-y-2">
                                {gateways.map((gateway) => (
                                    <label
                                        key={gateway}
                                        className="flex cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="radio"
                                            name="gateway"
                                            value={gateway}
                                            checked={data.gateway === gateway}
                                            onChange={() =>
                                                setData('gateway', gateway)
                                            }
                                            className="border-border text-primary"
                                        />
                                        <span className="capitalize">
                                            {gateway.replace('_', ' ')}
                                        </span>
                                        {gateway === 'mock' && (
                                            <span className="text-muted-foreground text-xs">
                                                (Demo — no real charge)
                                            </span>
                                        )}
                                    </label>
                                ))}
                            </div>

                            {!isMock && (
                                <div className="mt-3">
                                    <label className="mb-1 block text-sm font-medium">
                                        Promo code (optional)
                                    </label>
                                    <input
                                        type="text"
                                        value={data.coupon_code}
                                        onChange={(e) =>
                                            setData(
                                                'coupon_code',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="SUMMER20"
                                        className="border-border bg-background w-full rounded-md border px-3 py-2 text-sm"
                                    />
                                </div>
                            )}
                        </div>

                        <button
                            onClick={handleCheckout}
                            disabled={processing}
                            className="bg-primary text-primary-foreground hover:bg-primary/90 flex w-full items-center justify-center gap-2 rounded-lg px-6 py-3 text-sm font-medium disabled:opacity-50"
                        >
                            <CreditCard className="h-4 w-4" />
                            {processing
                                ? 'Processing...'
                                : `Pay ${summary.total.toFixed(2)} USD`}
                        </button>

                        <p className="text-muted-foreground text-center text-xs">
                            By confirming, your booking will be created and
                            payment charged via the selected gateway.
                        </p>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}