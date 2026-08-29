<?php

namespace App\Http\Middleware;

use App\Shared\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Unauthenticated.');
        }

        $organizationId = $request->attributes->get('current_organization_id');

        foreach ($roles as $role) {
            $roleEnum = RoleEnum::tryFrom((int) $role) ?? $this->resolveRoleFromName($role);

            if ($roleEnum === null) {
                if ($user->hasRole($role, $organizationId)) {
                    return $next($request);
                }

                continue;
            }

            $roleName = $roleEnum->name;

            if ($organizationId !== null) {
                if ($user->hasRole($roleName, (string) $organizationId)) {
                    return $next($request);
                }
            } else {
                if ($user->hasRole($roleName)) {
                    return $next($request);
                }

                $roleId = $this->getRoleIdByName($roleName);

                if ($roleId === null) {
                    continue;
                }

                $hasInAnyOrg = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->where('role_id', $roleId)
                    ->whereNotNull('organization_id')
                    ->exists();

                if ($hasInAnyOrg) {
                    return $next($request);
                }
            }
        }

        throw new AccessDeniedHttpException('You do not have the required role to access this resource.');
    }

    protected function getRoleIdByName(string $name): ?int
    {
        $role = Role::where('name', $name)->first();

        return $role?->id;
    }

    protected function resolveRoleFromName(string $name): ?RoleEnum
    {
        foreach (RoleEnum::cases() as $case) {
            if ($case->name === $name || $case->label() === $name) {
                return $case;
            }
        }

        return null;
    }
}
