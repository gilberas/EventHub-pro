<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Domain\Cms\Models\BlogPost;
use App\Domain\Cms\Models\FaqItem;
use App\Domain\Cms\Models\Sponsor;
use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\PermissionRegistrar;

class AdminService
{
    public function listOrganizations(int $perPage = 15): LengthAwarePaginator
    {
        return Organization::withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function toggleOrganizationStatus(Organization $organization): Organization
    {
        $organization->update(['is_active' => ! $organization->is_active]);

        return $organization->fresh();
    }

    public function listUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function updateUserRole(User $user, string $roleName, ?Organization $organization = null): void
    {
        if ($organization) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        }

        $user->syncRoles([$roleName]);
    }

    /** @return array<int, array{id: int, log: mixed}>
     *  Uses Laravel Auditing (owen-it/laravel-auditing) to retrieve audit trail.
     *  Pulls recent N entries — for production scale, paginate and filter server-side.
     */
    public function getAuditLogs(int $perPage = 50): LengthAwarePaginator
    {
        $modelClass = config('audit.model', Audit::class);

        return $modelClass::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // --- CMS ---

    public function listBlogPosts(int $perPage = 15): LengthAwarePaginator
    {
        return BlogPost::orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function upsertBlogPost(array $data, ?BlogPost $post = null): BlogPost
    {
        if (! isset($data['slug'])) {
            $data['slug'] = BlogPost::generateSlug($data['title']);
        }

        if ($post) {
            $post->update($data);

            return $post->fresh();
        }

        if (isset($data['is_published']) && $data['is_published'] && ! isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        return BlogPost::create($data);
    }

    public function deleteBlogPost(BlogPost $post): void
    {
        $post->delete();
    }

    public function listFaq(int $perPage = 15): LengthAwarePaginator
    {
        return FaqItem::orderBy('sort_order')->paginate($perPage);
    }

    public function upsertFaq(array $data, ?FaqItem $item = null): FaqItem
    {
        if ($item) {
            $item->update($data);

            return $item->fresh();
        }

        return FaqItem::create($data);
    }

    public function deleteFaq(FaqItem $item): void
    {
        $item->delete();
    }

    public function listSponsors(): Collection
    {
        return Sponsor::orderBy('sort_order')->get();
    }

    public function upsertSponsor(array $data, ?Sponsor $sponsor = null): Sponsor
    {
        if ($sponsor) {
            $sponsor->update($data);

            return $sponsor->fresh();
        }

        return Sponsor::create($data);
    }

    public function deleteSponsor(Sponsor $sponsor): void
    {
        $sponsor->delete();
    }
}
