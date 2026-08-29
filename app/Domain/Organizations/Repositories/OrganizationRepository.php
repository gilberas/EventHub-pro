<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Repositories;

use App\Domain\Organizations\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OrganizationRepository
{
    public function findById(int $id): ?Organization
    {
        return Organization::find($id);
    }

    public function findBySlug(string $slug): ?Organization
    {
        return Organization::where('slug', $slug)->first();
    }

    public function findByDomain(string $domain): ?Organization
    {
        return Organization::where('domain', $domain)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): ?Organization
    {
        $organization->update($data);

        return $organization->fresh();
    }

    public function delete(Organization $organization): bool
    {
        return (bool) $organization->delete();
    }

    /** @return Collection<int, Organization> */
    public function all(): Collection
    {
        return Organization::all();
    }

    /** @return LengthAwarePaginator<int, Organization> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Organization::paginate($perPage);
    }
}
