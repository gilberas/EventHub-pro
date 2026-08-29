<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Venues\Models\Section;
use App\Domain\Venues\Models\Venue;
use App\Shared\Enums\TicketMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds ticket types for every published event session and creates a
 * reserved-seating venue/hall/seats grid for a couple of events so the
 * seat-map booking flow is demonstrable end to end.
 *
 * Idempotent: skips sessions that already have ticket types and venues
 * that already exist, so it can safely backfill an existing database.
 */
class DemoTicketDataSeeder extends Seeder
{
    private const RESERVED_EVENTS = ['Laracon US 2026', 'Indie Rock Night'];

    public function run(): void
    {
        Event::with('sessions')
            ->where('status', 'published')
            ->get()
            ->each(function (Event $event) {
                $event->sessions->each(function (EventSession $session) use ($event) {
                    if ($session->ticketTypes()->exists()) {
                        return;
                    }

                    if (in_array($event->title, self::RESERVED_EVENTS, true)) {
                        $this->seedReservedSession($event, $session);
                    } else {
                        $this->seedGeneralAdmissionSession($event, $session);
                    }
                });
            });
    }

    private function seedReservedSession(Event $event, EventSession $session): void
    {
        $venue = $this->reservedVenue($event->organization_id);
        $session->update(['venue_id' => $venue->id, 'location' => 'New York, NY']);

        $session->ticketTypes()->create([
            'name' => 'Reserved Seating',
            'mode' => TicketMode::Reserved,
            'price' => $this->priceForTitle($event->title, 49.99),
            'quantity_available' => null,
            'max_per_order' => 4,
            'sort_order' => 0,
        ]);

        $this->command?->info("Seeded reserved seating for {$event->title}");
    }

    private function seedGeneralAdmissionSession(Event $event, EventSession $session): void
    {
        $seedPrice = $this->priceForTitle($event->title, 25.00);

        $tiers = [
            ['name' => 'General Admission', 'price' => $seedPrice, 'quantity_available' => 500, 'sort_order' => 0],
            ['name' => 'VIP', 'price' => round($seedPrice * 2.2, 2), 'quantity_available' => 100, 'sort_order' => 1],
        ];

        foreach ($tiers as $tier) {
            $session->ticketTypes()->create([
                'name' => $tier['name'],
                'mode' => TicketMode::GeneralAdmission,
                'price' => $tier['price'],
                'quantity_available' => $tier['quantity_available'],
                'max_per_order' => 10,
                'sort_order' => $tier['sort_order'],
            ]);
        }

        $this->command?->info("Seeded general admission ticket types for {$event->title}");
    }

    private function reservedVenue(int $organizationId): Venue
    {
        $venue = Venue::firstOrCreate(
            ['slug' => 'techcon-convention-center'],
            [
                'organization_id' => $organizationId,
                'name' => 'TechCon Convention Center',
                'slug' => 'techcon-convention-center',
                'description' => 'Demo convention center with a full reserved-seating hall.',
                'address' => '1 Convention Plaza',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'zip' => '10001',
                'is_active' => true,
            ],
        );

        $hall = $venue->halls()->firstOrCreate(
            ['name' => 'Main Hall'],
            ['description' => 'Main reserved-seating auditorium', 'capacity' => 240],
        );

        $orchestra = $hall->sections()->firstOrCreate(
            ['name' => 'Orchestra', 'sort_order' => 0],
            ['section_type' => 'standard', 'color' => '#3b82f6', 'capacity' => 120, 'sort_order' => 0],
        );
        $this->seedSectionGrid($orchestra, 3, 8, 'standard');

        $balcony = $hall->sections()->firstOrCreate(
            ['name' => 'Balcony', 'sort_order' => 1],
            ['section_type' => 'vip', 'color' => '#a855f7', 'capacity' => 64, 'sort_order' => 1],
        );
        $this->seedSectionGrid($balcony, 2, 6, 'vip');

        return $venue;
    }

    private function seedSectionGrid(Section $section, int $rows, int $seatsPerRow, string $seatType): void
    {
        if ($section->rows()->count() > 0) {
            return;
        }

        $section->generateGrid(['rows' => $rows, 'seats_per_row' => $seatsPerRow, 'seat_type' => $seatType]);
    }

    private function priceForTitle(string $title, float $fallback): float
    {
        $hash = crc32(Str::lower($title));

        return round($fallback + ($hash % 40), 2);
    }
}
