<?php

declare(strict_types=1);

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTOs\PaymentResult;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{
    private readonly StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(
            (string) config('services.stripe.secret'),
        );
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function charge(array $params): PaymentResult
    {
        try {
            $charge = $this->stripe->paymentIntents->create([
                'amount' => (int) round($params['amount'] * 100),
                'currency' => strtolower($params['currency'] ?? 'usd'),
                'payment_method' => $params['payment_method_id'] ?? null,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => $params['return_url'] ?? null,
                'metadata' => [
                    'booking_reference' => $params['booking_reference'] ?? '',
                    'booking_id' => (string) ($params['booking_id'] ?? ''),
                ],
            ]);

            return new PaymentResult(
                success: $charge->status === 'succeeded' || $charge->status === 'requires_capture',
                transactionId: $charge->id,
                amountCharged: $params['amount'] ?? null,
                currency: $params['currency'] ?? 'USD',
                rawResponse: $charge->toArray(),
            );
        } catch (\Throwable $e) {
            return new PaymentResult(
                success: false,
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function refund(string $transactionId, ?float $amount = null): PaymentResult
    {
        try {
            $params = ['payment_intent' => $transactionId];

            if ($amount !== null) {
                $params['amount'] = (int) round($amount * 100);
            }

            $refund = $this->stripe->refunds->create($params);

            $status = $refund->status;

            return new PaymentResult(
                success: $status === 'succeeded',
                transactionId: $refund->id,
                amountRefunded: $amount,
                rawResponse: $refund->toArray(),
            );
        } catch (\Throwable $e) {
            return new PaymentResult(
                success: false,
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function verify(string $transactionId): PaymentResult
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($transactionId);

            return new PaymentResult(
                success: $intent->status === 'succeeded',
                transactionId: $intent->id,
                amountCharged: $intent->amount ? $intent->amount / 100 : null,
                currency: strtoupper($intent->currency ?? 'USD'),
                rawResponse: $intent->toArray(),
            );
        } catch (\Throwable $e) {
            return new PaymentResult(
                success: false,
                errorMessage: $e->getMessage(),
            );
        }
    }
}
