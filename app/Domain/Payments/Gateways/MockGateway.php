<?php

declare(strict_types=1);

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTOs\PaymentResult;

/**
 * Development-only payment gateway.
 *
 * The mock gateway accepts every charge and returns a deterministic success
 * result so the full customer journey (hold -> checkout -> payment -> tickets)
 * can be exercised locally without real payment providers.
 *
 * It must never be used in production. Guard by PAYMENT_GATEWAY=mock only in
 * local/staging environments and by the application environment check below.
 */
class MockGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mock';
    }

    public function charge(array $params): PaymentResult
    {
        $amount = (float) ($params['amount'] ?? 0);
        $currency = (string) ($params['currency'] ?? 'USD');
        $reference = (string) ($params['booking_reference'] ?? '');

        return new PaymentResult(
            success: true,
            transactionId: 'mock_'.strtoupper(substr(bin2hex(random_bytes(8)), 0, 16)),
            amountCharged: $amount,
            currency: strtoupper($currency),
            rawResponse: [
                'provider' => $this->name(),
                'status' => 'succeeded',
                'booking_reference' => $reference,
                'mode' => 'development',
            ],
        );
    }

    public function refund(string $transactionId, ?float $amount = null): PaymentResult
    {
        return new PaymentResult(
            success: true,
            transactionId: 'mock_refund_'.strtoupper(substr(bin2hex(random_bytes(8)), 0, 16)),
            amountRefunded: $amount,
            currency: null,
            rawResponse: [
                'provider' => $this->name(),
                'status' => 'succeeded',
                'original_transaction' => $transactionId,
                'mode' => 'development',
            ],
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        return new PaymentResult(
            success: true,
            transactionId: $transactionId,
            rawResponse: [
                'provider' => $this->name(),
                'status' => 'succeeded',
                'mode' => 'development',
            ],
        );
    }
}
