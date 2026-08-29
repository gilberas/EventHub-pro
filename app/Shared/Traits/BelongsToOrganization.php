<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Domain\Organizations\Models\Organization;
use App\Shared\Scopes\OrganizationScope;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @param Builder $query */
    public function scopeForCurrentTenant($query): mixed
    {
        $orgId = OrganizationScope::$tenantResolver
            ? (OrganizationScope::$tenantResolver)()
            : null;

        if ($orgId === null) {
            return $query;
        }

        return $query->where('organization_id', $orgId);
    }

    /** @param Builder $query */
    public function scopeForOrganization($query, int $orgId): mixed
    {
        return $query->where('organization_id', $orgId);
    }
}
