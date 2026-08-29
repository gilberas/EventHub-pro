<?php

declare(strict_types=1);

namespace App\Shared\Scopes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** @implements Scope<Model> */
class OrganizationScope implements Scope
{
    public static ?Closure $tenantResolver = null;

    public static function resolveOrganizationIdUsing(?Closure $resolver): void
    {
        self::$tenantResolver = $resolver;
    }

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$tenantResolver === null) {
            return;
        }

        $orgId = (self::$tenantResolver)();

        if ($orgId === null) {
            return;
        }

        $builder->where('organization_id', $orgId);
    }
}
