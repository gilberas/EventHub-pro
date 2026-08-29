<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum SeatHoldStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Converted => 'Converted',
        };
    }
}
