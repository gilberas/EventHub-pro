<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum RoleEnum: int
{
    case Guest = 0;
    case Customer = 1;
    case TicketScanner = 2;
    case SupportAgent = 3;
    case EventManager = 4;
    case FinanceManager = 5;
    case OrganizationAdmin = 6;
    case OrganizationOwner = 7;
    case PlatformAdmin = 8;
    case SuperAdministrator = 9;

    public function label(): string
    {
        return match ($this) {
            self::Guest => 'Guest',
            self::Customer => 'Customer',
            self::TicketScanner => 'Ticket Scanner',
            self::SupportAgent => 'Support Agent',
            self::EventManager => 'Event Manager',
            self::FinanceManager => 'Finance Manager',
            self::OrganizationAdmin => 'Organization Admin',
            self::OrganizationOwner => 'Organization Owner',
            self::PlatformAdmin => 'Platform Admin',
            self::SuperAdministrator => 'Super Administrator',
        };
    }

    public function isPlatformRole(): bool
    {
        return match ($this) {
            self::PlatformAdmin, self::SuperAdministrator => true,
            default => false,
        };
    }

    public function isOrganizationRole(): bool
    {
        return match ($this) {
            self::Guest, self::Customer => false,
            default => true,
        };
    }
}
