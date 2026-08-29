<?php

declare(strict_types=1);

namespace App\Domain\Venues\DTOs;

class VenueDTO
{
    /**
     * @param  array<int, array{name: string, sections: array<int, array{name: string, rows: int, seats_per_row: int, seat_type?: string, color?: string}>}>  $layout
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zip = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $phone = null,
        public ?string $website = null,
        public bool $isActive = true,
        public array $layout = [],
    ) {}
}
