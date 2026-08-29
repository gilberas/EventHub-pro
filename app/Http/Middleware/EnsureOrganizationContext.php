<?php

namespace App\Http\Middleware;

use App\Domain\Organizations\Models\Organization;
use App\Shared\Scopes\OrganizationScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureOrganizationContext
{
    /** @var array<int, string> */
    protected array $except = [
        'login',
        'register',
        'password.*',
        'organization.select',
        'organization.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $this->resolveOrganizationId($request);

        if ($organizationId === null && $this->routesInOrganizationContext($request)) {
            return Redirect::route('dashboard');
        }

        if ($organizationId !== null) {
            OrganizationScope::resolveOrganizationIdUsing(fn () => $organizationId);
        }

        $request->attributes->set('current_organization_id', $organizationId);

        return $next($request);
    }

    protected function resolveOrganizationId(Request $request): ?int
    {
        if ($organizationId = $request->session()->get('current_organization_id')) {
            return (int) $organizationId;
        }

        $host = $request->getHost();

        $slug = Str::before($host, '.');

        if ($slug === $host || $slug === 'www' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($organization === null) {
            throw new NotFoundHttpException('Invalid organization domain.');
        }

        return $organization->id;
    }

    protected function routesInOrganizationContext(Request $request): bool
    {
        if ($request->route() === null) {
            return false;
        }

        $routeName = $request->route()->getName();

        if ($routeName === null) {
            return false;
        }

        foreach ($this->except as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return false;
            }
        }

        return str_starts_with($routeName, 'organizations.')
            || str_starts_with($request->route()->uri(), 'org/')
            || str_starts_with($request->route()->uri(), 'admin/');
    }
}
