<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Domain\Payments\Models\RefundRequest;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\RefundStatus;
use App\Shared\Enums\TransactionType;
use Illuminate\Support\Facades\DB;

class RefundWorkflow
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function requestRefund(Booking $booking, User $user, float $amount, string $reason, ?string $notes = null): RefundRequest
    {
        return RefundRequest::create([
            'booking_id' => $booking->id,
            'requested_by_user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
            'customer_notes' => $notes,
            'status' => RefundStatus::Pending,
        ]);
    }

    public function approve(RefundRequest $refundRequest, User $reviewer): RefundRequest
    {
        return DB::transaction(function () use ($refundRequest, $reviewer) {
            $locked = RefundRequest::lockForUpdate()->find($refundRequest->id);

            if ($locked->status !== RefundStatus::Pending) {
                throw new \RuntimeException('Refund request has already been processed.');
            }

            $booking = $locked->booking;

            $gatewayResult = $this->gateway->refund(
                $booking->transactions()->first()?->transaction_id ?? '',
                (float) $locked->amount,
            );

            if (! $gatewayResult->success) {
                $locked->update([
                    'status' => RefundStatus::Rejected,
                    'reviewed_by_user_id' => $reviewer->id,
                    'review_notes' => 'Refund failed at gateway: '.$gatewayResult->errorMessage,
                    'reviewed_at' => now(),
                ]);

                throw new \RuntimeException('Refund failed at payment gateway: '.$gatewayResult->errorMessage);
            }

            $locked->update([
                'status' => RefundStatus::Refunded,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            PaymentTransaction::create([
                'payable_type' => get_class($booking),
                'payable_id' => $booking->id,
                'gateway' => $this->gateway->name(),
                'transaction_id' => $gatewayResult->transactionId,
                'type' => (float) $locked->amount >= (float) $booking->total
                    ? TransactionType::Refund
                    : TransactionType::PartialRefund,
                'amount' => (float) $locked->amount,
                'currency' => $booking->currency,
                'status' => 'succeeded',
                'raw_response' => $gatewayResult->rawResponse,
            ]);

            $isFullRefund = abs((float) $locked->amount - (float) $booking->total) < 0.01;
            $booking->update([
                'status' => $isFullRefund ? BookingStatus::Refunded : BookingStatus::PartiallyRefunded,
            ]);

            return $locked->fresh();
        });
    }

    public function reject(RefundRequest $refundRequest, User $reviewer, ?string $notes = null): RefundRequest
    {
        $refundRequest->update([
            'status' => RefundStatus::Rejected,
            'reviewed_by_user_id' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        return $refundRequest->fresh();
    }
}
