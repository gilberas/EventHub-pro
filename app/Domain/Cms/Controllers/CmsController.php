<?php

declare(strict_types=1);

namespace App\Domain\Cms\Controllers;

use App\Domain\Admin\Services\AdminService;
use App\Domain\Cms\Models\BlogPost;
use App\Domain\Cms\Models\FaqItem;
use App\Domain\Cms\Models\Sponsor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    // --- Blog ---

    public function blogIndex(): Response
    {
        $posts = $this->adminService->listBlogPosts();

        return Inertia::render('Admin/Cms/Blog', ['posts' => $posts]);
    }

    public function blogStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'author_name' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        $this->adminService->upsertBlogPost($validated);

        return redirect()->route('admin.cms.blog')->with('success', 'Blog post created.');
    }

    public function blogUpdate(Request $request, BlogPost $post): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'author_name' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        $this->adminService->upsertBlogPost($validated, $post);

        return redirect()->route('admin.cms.blog')->with('success', 'Blog post updated.');
    }

    public function blogDestroy(BlogPost $post): RedirectResponse
    {
        $this->adminService->deleteBlogPost($post);

        return redirect()->route('admin.cms.blog')->with('success', 'Blog post deleted.');
    }

    // --- FAQ ---

    public function faqIndex(): Response
    {
        $items = $this->adminService->listFaq();

        return Inertia::render('Admin/Cms/Faq', ['items' => $items]);
    }

    public function faqStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
        ]);

        $this->adminService->upsertFaq($validated);

        return redirect()->route('admin.cms.faq')->with('success', 'FAQ item created.');
    }

    public function faqUpdate(Request $request, FaqItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
        ]);

        $this->adminService->upsertFaq($validated, $item);

        return redirect()->route('admin.cms.faq')->with('success', 'FAQ item updated.');
    }

    public function faqDestroy(FaqItem $item): RedirectResponse
    {
        $this->adminService->deleteFaq($item);

        return redirect()->route('admin.cms.faq')->with('success', 'FAQ item deleted.');
    }

    // --- Sponsors ---

    public function sponsorIndex(): Response
    {
        $sponsors = $this->adminService->listSponsors();

        return Inertia::render('Admin/Cms/Sponsors', ['sponsors' => $sponsors]);
    }

    public function sponsorStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'nullable|url|max:500',
            'tier' => 'required|in:bronze,silver,gold,platinum',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->adminService->upsertSponsor($validated);

        return redirect()->route('admin.cms.sponsors')->with('success', 'Sponsor created.');
    }

    public function sponsorUpdate(Request $request, Sponsor $sponsor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'nullable|url|max:500',
            'tier' => 'required|in:bronze,silver,gold,platinum',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->adminService->upsertSponsor($validated, $sponsor);

        return redirect()->route('admin.cms.sponsors')->with('success', 'Sponsor updated.');
    }

    public function sponsorDestroy(Sponsor $sponsor): RedirectResponse
    {
        $this->adminService->deleteSponsor($sponsor);

        return redirect()->route('admin.cms.sponsors')->with('success', 'Sponsor deleted.');
    }
}
