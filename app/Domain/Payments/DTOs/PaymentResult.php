<?php

declare(strict_types=1);

namespace App\Domain\Payments\DTOs;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $errorMessage = null,
        public readonly ?array $rawResponse = null,
        public readonly ?float $amountCharged = null,
        public readonly ?float $amountRefunded = null,
        public readonly ?string $currency = null,
    ) {}
}
