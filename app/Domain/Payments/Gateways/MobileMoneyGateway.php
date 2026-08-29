<?php

declare(strict_types=1);

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTOs\PaymentResult;

class MobileMoneyGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mobile_money';
    }

    public function charge(array $params): PaymentResult
    {
        return new PaymentResult(
            success: false,
            errorMessage: 'Mobile money gateway not yet implemented',
        );
    }

    public function refund(string $transactionId, ?float $amount = null): PaymentResult
    {
        return new PaymentResult(
            success: false,
            errorMessage: 'Mobile money gateway not yet implemented',
        );
    }

    public function verify(string $transactionId): PaymentResult
    {
        return new PaymentResult(
            success: false,
            errorMessage: 'Mobile money gateway not yet implemented',
        );
    }
}
