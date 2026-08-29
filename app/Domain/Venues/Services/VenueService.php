<?php

declare(strict_types=1);

namespace App\Domain\Venues\Services;

use App\Domain\Venues\DTOs\VenueDTO;
use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Repositories\VenueRepository;
use Illuminate\Database\Eloquent\Collection;

class VenueService
{
    public function __construct(
        private readonly VenueRepository $repository,
    ) {}

    /** @return Collection<int, Venue> */
    public function listForOrganization(int $organizationId): Collection
    {
        return $this->repository->listForOrganization($organizationId);
    }

    public function findById(int $id): ?Venue
    {
        return $this->repository->findById($id);
    }

    public function findBySlug(string $slug): ?Venue
    {
        return $this->repository->findBySlug($slug);
    }

    public function create(int $organizationId, VenueDTO $dto): Venue
    {
        $venue = $this->repository->create([
            'organization_id' => $organizationId,
            'name' => $dto->name,
            'slug' => Venue::generateSlug($dto->name),
            'description' => $dto->description,
            'address' => $dto->address,
            'city' => $dto->city,
            'state' => $dto->state,
            'country' => $dto->country,
            'zip' => $dto->zip,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
            'phone' => $dto->phone,
            'website' => $dto->website,
            'is_active' => $dto->isActive,
        ]);

        if (! empty($dto->layout)) {
            $this->generateLayout($venue, $dto->layout);
        }

        return $venue->fresh(['halls.sections.rows.seats']);
    }

    public function update(Venue $venue, VenueDTO $dto): Venue
    {
        return $this->repository->update($venue, [
            'name' => $dto->name,
            'slug' => Venue::generateSlug($dto->name, $venue->id),
            'description' => $dto->description,
            'address' => $dto->address,
            'city' => $dto->city,
            'state' => $dto->state,
            'country' => $dto->country,
            'zip' => $dto->zip,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
            'phone' => $dto->phone,
            'website' => $dto->website,
            'is_active' => $dto->isActive,
        ]);
    }

    public function delete(Venue $venue): bool
    {
        return $this->repository->delete($venue);
    }

    public function duplicateAsTemplate(Venue $venue, int $newOrganizationId): Venue
    {
        return $this->repository->duplicateAsTemplate($venue, $newOrganizationId);
    }

    /**
     * @param  array<int, array{name: string, sections: array<int, array{name: string, rows: int, seats_per_row: int, seat_type?: string, color?: string}>}>  $layoutData
     */
    private function generateLayout(Venue $venue, array $layoutData): void
    {
        foreach ($layoutData as $hallData) {
            $hall = $venue->halls()->create([
                'name' => $hallData['name'],
                'capacity' => 0,
            ]);

            foreach ($hallData['sections'] as $sectionData) {
                $section = $hall->sections()->create([
                    'name' => $sectionData['name'],
                    'section_type' => $sectionData['seat_type'] ?? 'standard',
                    'color' => $sectionData['color'] ?? null,
                    'capacity' => $sectionData['rows'] * $sectionData['seats_per_row'],
                    'sort_order' => 0,
                ]);

                $section->generateGrid([
                    'rows' => $sectionData['rows'],
                    'seats_per_row' => $sectionData['seats_per_row'],
                    'seat_type' => $sectionData['seat_type'] ?? 'standard',
                ]);
            }

            $hall->update(['capacity' => $hall->sections->sum('capacity')]);
        }
    }
}
