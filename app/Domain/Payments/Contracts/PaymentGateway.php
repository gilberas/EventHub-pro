<?php

declare(strict_types=1);

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\DTOs\PaymentResult;

interface PaymentGateway
{
    public function charge(array $params): PaymentResult;

    public function refund(string $transactionId, ?float $amount = null): PaymentResult;

    public function verify(string $transactionId): PaymentResult;

    public function name(): string;
}
