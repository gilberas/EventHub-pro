<?php

declare(strict_types=1);

use App\Domain\Admin\Services\AdminService;
use App\Domain\Cms\Models\BlogPost;
use App\Domain\Cms\Models\FaqItem;
use App\Domain\Cms\Models\Sponsor;
use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Enums\RoleEnum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = new AdminService;
});

// ---- Organizations ----

it('lists organizations with user count', function () {
    Organization::factory()->count(3)->create();
    $result = $this->admin->listOrganizations();
    expect($result)->toHaveCount(3);
});

it('toggles organization status', function () {
    $org = Organization::factory()->create(['is_active' => true]);
    $this->admin->toggleOrganizationStatus($org);
    expect($org->fresh()->is_active)->toBeFalse();
    $this->admin->toggleOrganizationStatus($org);
    expect($org->fresh()->is_active)->toBeTrue();
});

// ---- Users ----

it('lists users with roles', function () {
    User::factory()->count(3)->create();
    $result = $this->admin->listUsers();
    expect($result)->toHaveCount(3);
});

it('updates user role with organization scope', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $this->admin->updateUserRole($user, RoleEnum::OrganizationAdmin->name, $org);
    expect($user->hasRole(RoleEnum::OrganizationAdmin->name))->toBeTrue();
});

it('updates user role without organization scope', function () {
    $user = User::factory()->create();
    $this->admin->updateUserRole($user, RoleEnum::PlatformAdmin->name);
    expect($user->hasRole(RoleEnum::PlatformAdmin->name))->toBeTrue();
});

// ---- Blog Posts ----

it('lists blog posts', function () {
    BlogPost::create(['title' => 'Test', 'slug' => 'test', 'content' => 'Content', 'author_name' => 'Author']);
    $result = $this->admin->listBlogPosts();
    expect($result)->toHaveCount(1);
});

it('creates a blog post', function () {
    $post = $this->admin->upsertBlogPost(['title' => 'New Post', 'content' => 'Body', 'author_name' => 'Me']);
    expect($post->title)->toBe('New Post');
    expect($post->slug)->not->toBeNull();
});

it('creates a published blog post with auto published_at', function () {
    $post = $this->admin->upsertBlogPost(['title' => 'Pub', 'content' => 'Body', 'author_name' => 'Me', 'is_published' => true]);
    expect($post->is_published)->toBeTrue();
    expect($post->published_at)->not->toBeNull();
});

it('updates a blog post', function () {
    $post = BlogPost::create(['title' => 'Old', 'slug' => 'old', 'content' => 'C', 'author_name' => 'A']);
    $updated = $this->admin->upsertBlogPost(['title' => 'Updated'], $post);
    expect($updated->title)->toBe('Updated');
});

it('deletes a blog post', function () {
    $post = BlogPost::create(['title' => 'Del', 'slug' => 'del', 'content' => 'C', 'author_name' => 'A']);
    $this->admin->deleteBlogPost($post);
    expect(BlogPost::count())->toBe(0);
});

// ---- FAQ ----

it('lists faq items ordered by sort_order', function () {
    FaqItem::create(['question' => 'Q2', 'answer' => 'A2', 'sort_order' => 2]);
    FaqItem::create(['question' => 'Q1', 'answer' => 'A1', 'sort_order' => 1]);
    $result = $this->admin->listFaq();
    expect($result->first()->question)->toBe('Q1');
});

it('creates an faq item', function () {
    $item = $this->admin->upsertFaq(['question' => 'Q', 'answer' => 'A', 'sort_order' => 1]);
    expect($item->question)->toBe('Q');
});

it('updates an faq item', function () {
    $item = FaqItem::create(['question' => 'Q', 'answer' => 'A', 'sort_order' => 1]);
    $updated = $this->admin->upsertFaq(['question' => 'Updated'], $item);
    expect($updated->question)->toBe('Updated');
});

it('deletes an faq item', function () {
    $item = FaqItem::create(['question' => 'Q', 'answer' => 'A', 'sort_order' => 1]);
    $this->admin->deleteFaq($item);
    expect(FaqItem::count())->toBe(0);
});

// ---- Sponsors ----

it('lists sponsors ordered by sort_order', function () {
    Sponsor::create(['name' => 'S2', 'sort_order' => 2]);
    Sponsor::create(['name' => 'S1', 'sort_order' => 1]);
    $result = $this->admin->listSponsors();
    expect($result->first()->name)->toBe('S1');
});

it('creates a sponsor', function () {
    $sponsor = $this->admin->upsertSponsor(['name' => 'Sponsor', 'sort_order' => 1]);
    expect($sponsor->name)->toBe('Sponsor');
});

it('updates a sponsor', function () {
    $sponsor = Sponsor::create(['name' => 'Old', 'sort_order' => 1]);
    $updated = $this->admin->upsertSponsor(['name' => 'Updated'], $sponsor);
    expect($updated->name)->toBe('Updated');
});

it('deletes a sponsor', function () {
    $sponsor = Sponsor::create(['name' => 'Del', 'sort_order' => 1]);
    $this->admin->deleteSponsor($sponsor);
    expect(Sponsor::count())->toBe(0);
});
