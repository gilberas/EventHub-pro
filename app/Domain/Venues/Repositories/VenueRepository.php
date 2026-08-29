<?php

declare(strict_types=1);

namespace App\Domain\Venues\Repositories;

use App\Domain\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Collection;

class VenueRepository
{
    /** @return Collection<int, Venue> */
    public function listForOrganization(int $organizationId): Collection
    {
        return Venue::with(['halls.sections.rows.seats'])
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Venue
    {
        return Venue::with(['halls.sections.rows.seats'])->find($id);
    }

    public function findBySlug(string $slug): ?Venue
    {
        return Venue::with(['halls.sections.rows.seats'])->where('slug', $slug)->first();
    }

    public function create(array $data): Venue
    {
        return Venue::create($data);
    }

    public function update(Venue $venue, array $data): Venue
    {
        $venue->update($data);

        return $venue->fresh(['halls.sections.rows.seats']);
    }

    public function delete(Venue $venue): bool
    {
        return $venue->delete() !== null;
    }

    public function duplicateAsTemplate(Venue $original, int $newOrganizationId): Venue
    {
        $copy = $this->create([
            'organization_id' => $newOrganizationId,
            'name' => 'Copy of '.$original->name,
            'slug' => Venue::generateSlug('Copy of '.$original->name),
            'description' => $original->description,
            'address' => $original->address,
            'city' => $original->city,
            'state' => $original->state,
            'country' => $original->country,
            'zip' => $original->zip,
            'latitude' => $original->latitude,
            'longitude' => $original->longitude,
            'phone' => $original->phone,
            'website' => $original->website,
            'is_active' => false,
        ]);

        foreach ($original->halls as $hall) {
            $newHall = $copy->halls()->create([
                'name' => $hall->name,
                'description' => $hall->description,
                'capacity' => $hall->capacity,
            ]);

            foreach ($hall->sections as $section) {
                $newSection = $newHall->sections()->create([
                    'name' => $section->name,
                    'section_type' => $section->section_type,
                    'color' => $section->color,
                    'capacity' => $section->capacity,
                    'sort_order' => $section->sort_order,
                ]);

                foreach ($section->rows as $row) {
                    $newRow = $newSection->rows()->create([
                        'label' => $row->label,
                        'sort_order' => $row->sort_order,
                    ]);

                    foreach ($row->seats as $seat) {
                        $newRow->seats()->create([
                            'seat_number' => $seat->seat_number,
                            'type' => $seat->type,
                            'row_position' => $seat->row_position,
                            'col_position' => $seat->col_position,
                            'x_coord' => $seat->x_coord,
                            'y_coord' => $seat->y_coord,
                            'is_active' => $seat->is_active,
                        ]);
                    }
                }
            }
        }

        return $copy->fresh(['halls.sections.rows.seats']);
    }
}
