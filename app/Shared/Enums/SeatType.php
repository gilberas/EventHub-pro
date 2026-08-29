<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum SeatType: string
{
    case Standard = 'standard';
    case Vip = 'vip';
    case Premium = 'premium';
    case Wheelchair = 'wheelchair';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Vip => 'VIP',
            self::Premium => 'Premium',
            self::Wheelchair => 'Wheelchair',
        };
    }
}
