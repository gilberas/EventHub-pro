<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum TransactionType: string
{
    case Charge = 'charge';
    case Refund = 'refund';
    case PartialRefund = 'partial_refund';

    public function label(): string
    {
        return match ($this) {
            self::Charge => 'Charge',
            self::Refund => 'Refund',
            self::PartialRefund => 'Partial Refund',
        };
    }
}
