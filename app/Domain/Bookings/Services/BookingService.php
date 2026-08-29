<?php

declare(strict_types=1);

namespace App\Domain\Bookings\Services;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Bookings\Models\BookingSeat;
use App\Domain\Bookings\Models\SeatHold;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\EventSession;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Domain\Payments\Services\CouponService;
use App\Domain\Payments\Services\GiftCardService;
use App\Domain\Payments\Services\InvoiceService;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\SeatHoldStatus;
use App\Shared\Enums\TransactionType;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly GiftCardService $giftCardService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function holdSeats(EventSession $session, TicketType $ticketType, User $user, array $seatIds): array
    {
        return DB::transaction(function () use ($session, $ticketType, $user, $seatIds) {
            $holds = [];

            foreach ($seatIds as $seatId) {
                $existingHold = SeatHold::where('event_session_id', $session->id)
                    ->where('seat_id', $seatId)
                    ->where('status', SeatHoldStatus::Active)
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if ($existingHold) {
                    throw new \RuntimeException("Seat {$seatId} is already held by another user.");
                }

                $existingBooking = BookingSeat::where('event_session_id', $session->id)
                    ->where('seat_id', $seatId)
                    ->lockForUpdate()
                    ->first();

                if ($existingBooking) {
                    throw new \RuntimeException("Seat {$seatId} is already booked.");
                }

                $holds[] = SeatHold::create([
                    'event_session_id' => $session->id,
                    'ticket_type_id' => $ticketType->id,
                    'seat_id' => $seatId,
                    'user_id' => $user->id,
                    'quantity' => 1,
                    'status' => SeatHoldStatus::Active,
                    'expires_at' => now()->addMinutes(10),
                ]);
            }

            return $holds;
        });
    }

    public function holdGATickets(EventSession $session, TicketType $ticketType, User $user, int $quantity): SeatHold
    {
        return DB::transaction(function () use ($session, $ticketType, $user, $quantity) {
            $locked = TicketType::lockForUpdate()->find($ticketType->id);

            if (! $locked || $locked->quantity_available === null || $locked->quantity_available < $quantity) {
                throw new \RuntimeException('Not enough tickets available.');
            }

            $locked->decrement('quantity_available', $quantity);

            return SeatHold::create([
                'event_session_id' => $session->id,
                'ticket_type_id' => $ticketType->id,
                'seat_id' => null,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'status' => SeatHoldStatus::Active,
                'expires_at' => now()->addMinutes(10),
            ]);
        });
    }

    public function checkout(User $user, array $holdIds): Booking
    {
        return DB::transaction(function () use ($user, $holdIds) {
            $holds = SeatHold::whereIn('id', $holdIds)
                ->where('user_id', $user->id)
                ->where('status', SeatHoldStatus::Active)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->get();

            if ($holds->isEmpty()) {
                throw new \RuntimeException('No valid holds found. Your held seats may have expired.');
            }

            $sessionId = $holds->first()->event_session_id;
            $reference = Booking::generateReference();

            $ticketTypeTotals = $holds->groupBy('ticket_type_id')->map(function ($group) {
                $tt = $group->first()->ticketType;

                return [
                    'quantity' => $group->sum('quantity'),
                    'unit_price' => $tt->price,
                    'subtotal' => $group->sum('quantity') * (float) $tt->price,
                ];
            });

            $subtotal = (float) $ticketTypeTotals->sum('subtotal');
            $fees = round($subtotal * 0.05, 2);
            $total = $subtotal + $fees;

            $booking = Booking::create([
                'user_id' => $user->id,
                'event_session_id' => $sessionId,
                'reference' => $reference,
                'status' => BookingStatus::PendingPayment,
                'subtotal' => $subtotal,
                'fees' => $fees,
                'total' => $total,
                'currency' => 'USD',
            ]);

            foreach ($ticketTypeTotals as $ticketTypeId => $totals) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'ticket_type_id' => $ticketTypeId,
                    'quantity' => $totals['quantity'],
                    'unit_price' => $totals['unit_price'],
                    'subtotal' => $totals['subtotal'],
                ]);
            }

            foreach ($holds as $hold) {
                if ($hold->seat_id) {
                    BookingSeat::create([
                        'booking_id' => $booking->id,
                        'event_session_id' => $sessionId,
                        'seat_id' => $hold->seat_id,
                        'ticket_type_id' => $hold->ticket_type_id,
                    ]);
                }

                $hold->update(['status' => SeatHoldStatus::Converted]);
            }

            return $booking->fresh(['items', 'seats', 'eventSession.event']);
        });
    }

    public function checkoutWithPayment(
        User $user,
        array $holdIds,
        PaymentGateway $gateway,
        array $paymentParams,
        ?string $couponCode = null,
        ?string $giftCardCode = null,
    ): Booking {
        return DB::transaction(function () use ($user, $holdIds, $gateway, $paymentParams, $couponCode, $giftCardCode) {
            $booking = $this->checkout($user, $holdIds);
            $booking->load('eventSession.event.organization');

            $totalDue = (float) $booking->total;
            $discountTotal = 0.0;

            if ($couponCode && $this->couponService) {
                $org = $booking->eventSession?->event?->organization;
                if ($org) {
                    $coupon = $this->couponService->findByCode($couponCode, $org);
                    if ($coupon) {
                        $validation = $this->couponService->validateCoupon($coupon, $totalDue);
                        if ($validation['valid']) {
                            $discount = $this->couponService->apply($coupon, $totalDue);
                            $discountTotal += $discount;
                            $totalDue -= $discount;

                            DB::table('booking_coupons')->insert([
                                'booking_id' => $booking->id,
                                'coupon_id' => $coupon->id,
                                'discount_amount' => $discount,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            if ($giftCardCode && $this->giftCardService) {
                $org = $booking->eventSession?->event?->organization;
                if ($org) {
                    $giftCard = $this->giftCardService->findByCode($giftCardCode, $org);
                    if ($giftCard) {
                        $validation = $this->giftCardService->validateGiftCard($giftCard, $totalDue);
                        if ($validation['valid']) {
                            $amountUsed = $this->giftCardService->redeem($giftCard, $totalDue);
                            $totalDue -= $amountUsed;

                            DB::table('booking_gift_cards')->insert([
                                'booking_id' => $booking->id,
                                'gift_card_id' => $giftCard->id,
                                'amount_used' => $amountUsed,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            $amountToCharge = max($totalDue, 0);

            if ($amountToCharge > 0) {
                $result = $gateway->charge([
                    'amount' => $amountToCharge,
                    'currency' => $booking->currency,
                    'booking_reference' => $booking->reference,
                    'booking_id' => $booking->id,
                    'payment_method_id' => $paymentParams['payment_method_id'] ?? null,
                    'return_url' => $paymentParams['return_url'] ?? null,
                    'cancel_url' => $paymentParams['cancel_url'] ?? null,
                    'payer_id' => $paymentParams['payer_id'] ?? null,
                    'payment_id' => $paymentParams['payment_id'] ?? null,
                    'description' => sprintf('Event booking %s', $booking->reference),
                ]);

                if (! $result->success) {
                    PaymentTransaction::create([
                        'payable_type' => get_class($booking),
                        'payable_id' => $booking->id,
                        'gateway' => $gateway->name(),
                        'transaction_id' => $result->transactionId,
                        'type' => TransactionType::Charge,
                        'amount' => $amountToCharge,
                        'currency' => $booking->currency,
                        'status' => 'failed',
                        'error_message' => $result->errorMessage,
                    ]);

                    throw new \RuntimeException('Payment failed: '.$result->errorMessage);
                }

                PaymentTransaction::create([
                    'payable_type' => get_class($booking),
                    'payable_id' => $booking->id,
                    'gateway' => $gateway->name(),
                    'transaction_id' => $result->transactionId,
                    'type' => TransactionType::Charge,
                    'amount' => $amountToCharge,
                    'currency' => $booking->currency,
                    'status' => 'succeeded',
                    'raw_response' => $result->rawResponse,
                ]);
            }

            $booking->update([
                'status' => BookingStatus::Confirmed,
                'total' => $amountToCharge + $discountTotal,
                'paid_at' => now(),
            ]);

            if ($this->invoiceService) {
                $this->invoiceService->generate($booking, $discountTotal);
            }

            return $booking->fresh(['items', 'seats', 'transactions', 'invoices']);
        });
    }

    public function processRefund(Booking $booking, PaymentGateway $gateway, float $amount): void
    {
        DB::transaction(function () use ($booking, $gateway, $amount) {
            $locked = Booking::lockForUpdate()->find($booking->id);

            if ($locked->status === BookingStatus::PendingPayment) {
                throw new \RuntimeException('Cannot refund a booking that was never charged.');
            }

            $transaction = $locked->transactions()->where('type', TransactionType::Charge)->first();

            if (! $transaction || ! $transaction->transaction_id) {
                throw new \RuntimeException('No charge transaction found for this booking.');
            }

            $result = $gateway->refund($transaction->transaction_id, $amount);

            if (! $result->success) {
                throw new \RuntimeException('Refund failed: '.$result->errorMessage);
            }

            PaymentTransaction::create([
                'payable_type' => get_class($locked),
                'payable_id' => $locked->id,
                'gateway' => $gateway->name(),
                'transaction_id' => $result->transactionId,
                'type' => $amount >= (float) $locked->total
                    ? TransactionType::Refund
                    : TransactionType::PartialRefund,
                'amount' => $amount,
                'currency' => $locked->currency,
                'status' => 'succeeded',
                'raw_response' => $result->rawResponse,
            ]);

            $isFull = abs($amount - (float) $locked->total) < 0.01;
            $locked->update([
                'status' => $isFull ? BookingStatus::Refunded : BookingStatus::PartiallyRefunded,
            ]);
        });
    }

    public function cancelBooking(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            Booking::lockForUpdate()->find($booking->id);

            if ($booking->status === BookingStatus::Confirmed) {
                throw new \RuntimeException('Confirmed bookings cannot be cancelled online. Contact support.');
            }

            $booking->update(['status' => BookingStatus::Cancelled]);
        });
    }

    public function getEventCapacity(EventSession $session): array
    {
        $totalTicketCapacity = TicketType::where('event_session_id', $session->id)
            ->where('mode', 'general_admission')
            ->sum('quantity_available');

        $confirmedBookings = BookingSeat::where('event_session_id', $session->id)->count();

        $activeHolds = SeatHold::where('event_session_id', $session->id)
            ->where('status', SeatHoldStatus::Active)
            ->where('expires_at', '>', now())
            ->count();

        return [
            'total_capacity' => $session->capacity,
            'booked' => $confirmedBookings,
            'held' => $activeHolds,
            'available' => ($session->capacity ?? 999999) - $confirmedBookings - $activeHolds,
        ];
    }

    public function isSoldOut(EventSession $session): bool
    {
        $caps = $this->getEventCapacity($session);

        return $caps['available'] <= 0;
    }
}
