<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum TicketMode: string
{
    case Reserved = 'reserved';
    case GeneralAdmission = 'general_admission';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Reserved Seating',
            self::GeneralAdmission => 'General Admission',
        };
    }
}
