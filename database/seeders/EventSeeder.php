<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EventSeeder extends Seeder
{
    private function unsplashUrl(string $id, int $w = 1200, int $h = 700): string
    {
        return "https://images.unsplash.com/photo-{$id}?w={$w}&h={$h}&fit=crop&auto=format";
    }

    public function run(): void
    {
        $org = Organization::first();

        if ($org === null) {
            $this->command->warn('No organizations found. Skipping EventSeeder.');

            return;
        }

        $events = [
            [
                'title' => 'Neon Frequencies',
                'description' => 'A three-day electronic music festival featuring world-class DJs across five stages.',
                'category' => 'Festivals',
                'tags' => ['Electronic', 'Outdoor', 'Multi-day', 'Camping'],
                'is_featured' => true,
                'trending_score' => 98.5,
                'start_date' => now()->addWeeks(3),
                'image_id' => '1470229722913-7c0e2dbbafd3',
                'gallery_ids' => ['1506157786151-b8491531f063', '1493225457124-a3eb161ffa5f', '1429962714451-bb934ecdc4ec'],
            ],
            [
                'title' => 'Arctic Monkeys: The Comedown Machine Tour',
                'description' => 'The legendary Sheffield rock band returns to MSG for one unforgettable night.',
                'category' => 'Concerts',
                'tags' => ['Rock', 'Indie', 'Arena'],
                'is_featured' => true,
                'trending_score' => 95.0,
                'start_date' => now()->addWeeks(5),
                'image_id' => '1501281668745-f7f57925c3b4',
                'gallery_ids' => ['1514525253161-7a46d19cd819', '1492684223066-81342ee5ff30'],
            ],
            [
                'title' => 'TechSummit 2026',
                'description' => 'Three days. 200+ speakers. The world\'s leading technology conference.',
                'category' => 'Conferences',
                'tags' => ['Technology', 'Networking', 'Professional'],
                'is_featured' => true,
                'trending_score' => 88.4,
                'start_date' => now()->addWeeks(6),
                'image_id' => '1540575467063-178a50c2df87',
                'gallery_ids' => ['1556761175-b413da4baf72', '1524178232363-1fb2b075b655'],
            ],
            [
                'title' => 'Champions League Final 2026',
                'description' => 'The pinnacle of European club football. 90,000 fans. One trophy.',
                'category' => 'Sports',
                'tags' => ['Football', 'European', 'Championship'],
                'is_featured' => false,
                'trending_score' => 92.3,
                'start_date' => now()->addWeeks(8),
                'image_id' => '1461896836934-ffe607ba8211',
                'gallery_ids' => [],
            ],
            [
                'title' => 'Dave Chappelle: Midnight Return',
                'description' => '120 seats. No cameras. One of comedy\'s greatest performers in his natural habitat.',
                'category' => 'Comedy',
                'tags' => ['Stand-Up', 'Intimate', 'Late Night'],
                'is_featured' => false,
                'trending_score' => 85.1,
                'start_date' => now()->addWeeks(4),
                'image_id' => '1507003211169-0a1dd7228f2d',
                'gallery_ids' => [],
            ],
            [
                'title' => 'Sakura Cultural Festival',
                'description' => 'A celebration of Japanese culture, food, art, and performance in the heart of Brooklyn.',
                'category' => 'Cultural',
                'tags' => ['Japanese', 'Family', 'Outdoor', 'Free'],
                'is_featured' => false,
                'trending_score' => 74.2,
                'start_date' => now()->addWeeks(5),
                'image_id' => '1533174072545-7a4b6ad7a6c3',
                'gallery_ids' => [],
            ],
            [
                'title' => 'Jazz Under the Stars',
                'description' => 'Esperanza Spalding and Kamasi Washington share one legendary evening at the Hollywood Bowl.',
                'category' => 'Concerts',
                'tags' => ['Jazz', 'Outdoor', 'Orchestra'],
                'is_featured' => false,
                'trending_score' => 65.3,
                'start_date' => now()->addWeeks(2),
                'image_id' => '1514525253161-7a46d19cd819',
                'gallery_ids' => ['1429962714451-bb934ecdc4ec'],
            ],
            [
                'title' => 'Global Founders Summit',
                'description' => '500 founders, operators, and investors. One curated day of ideas and connections.',
                'category' => 'Business',
                'tags' => ['Startups', 'Investors', 'Exclusive'],
                'is_featured' => false,
                'trending_score' => 71.8,
                'start_date' => now()->addWeeks(7),
                'image_id' => '1556761175-b413da4baf72',
                'gallery_ids' => [],
            ],
            [
                'title' => 'Design Systems Workshop',
                'description' => 'A full-day hands-on workshop on building scalable design systems, led by experts from Apple and Stripe.',
                'category' => 'Workshops',
                'tags' => ['Design', 'Professional Development'],
                'is_featured' => false,
                'trending_score' => 78.9,
                'start_date' => now()->addWeeks(2),
                'image_id' => '1524178232363-1fb2b075b655',
                'gallery_ids' => [],
            ],
            [
                'title' => 'Midnight Techno: Warehouse Sessions',
                'description' => 'Nine hours of relentless techno in a converted industrial warehouse — the authentic Berlin experience.',
                'category' => 'Concerts',
                'tags' => ['Techno', 'Underground', 'All Night', 'Berlin'],
                'is_featured' => false,
                'trending_score' => 82.0,
                'start_date' => now()->addWeeks(2),
                'image_id' => '1492684223066-81342ee5ff30',
                'gallery_ids' => ['1493225457124-a3eb161ffa5f'],
            ],
            [
                'title' => 'Afrobeats & Afrofusion Night',
                'description' => 'Burna Boy, Wizkid, and Tems headline an explosive evening celebrating the global rise of Afrobeats.',
                'category' => 'Concerts',
                'tags' => ['Afrobeats', 'Live Music', 'Cultural'],
                'is_featured' => true,
                'trending_score' => 90.0,
                'start_date' => now()->addWeeks(4),
                'image_id' => '1429962714451-bb934ecdc4ec',
                'gallery_ids' => [],
            ],
            [
                'title' => 'World Street Food Championship',
                'description' => '120 chefs from 40 countries compete for the world\'s most coveted street food title.',
                'category' => 'Cultural',
                'tags' => ['Food', 'Cultural', 'Competition', 'Family'],
                'is_featured' => false,
                'trending_score' => 76.5,
                'start_date' => now()->addWeeks(6),
                'image_id' => '1504674900247-0877df9cc836',
                'gallery_ids' => [],
            ],
        ];

        foreach ($events as $eventData) {
            $startDate = $eventData['start_date'];
            $imageId = $eventData['image_id'];
            $galleryIds = $eventData['gallery_ids'];
            unset($eventData['start_date'], $eventData['image_id'], $eventData['gallery_ids']);

            $event = Event::create(array_merge($eventData, [
                'organization_id' => $org->id,
                'slug' => Str::slug($eventData['title']),
                'status' => 'published',
            ]));

            $event->sessions()->create([
                'start_date' => $startDate,
                'end_date' => (clone $startDate)->modify('+3 hours'),
                'capacity' => fake()->numberBetween(50, 2000),
                'sort_order' => 0,
            ]);

            // Download cover image from Unsplash and attach via Spatie Media Library
            try {
                $coverUrl = $this->unsplashUrl($imageId);
                $event->addMediaFromUrl($coverUrl)->toMediaCollection('cover');
                $this->command->info("  Cover image attached for: {$event->title}");
            } catch (\Exception $e) {
                $this->command->warn("  Failed to attach cover image for {$event->title}: {$e->getMessage()}");
            }

            // Download gallery images
            foreach ($galleryIds as $galleryId) {
                try {
                    $galleryUrl = $this->unsplashUrl($galleryId, 800, 500);
                    $event->addMediaFromUrl($galleryUrl)->toMediaCollection('gallery');
                } catch (\Exception $e) {
                    $this->command->warn("  Failed to attach gallery image for {$event->title}: {$e->getMessage()}");
                }
            }

            $this->command->info("Created event: {$event->title}");
        }
    }
}
