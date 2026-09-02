<?php

use App\Domain\Admin\Controllers\AdminOrganizationController;
use App\Domain\Admin\Controllers\AdminUserController;
use App\Domain\Admin\Controllers\AuditLogController;
use App\Domain\Admin\Controllers\SystemController;
use App\Domain\Analytics\Controllers\DashboardDataController;
use App\Domain\Bookings\Controllers\BookingController;
use App\Domain\Cms\Controllers\CmsController;
use App\Domain\Events\Controllers\EventController;
use App\Domain\Events\Controllers\FavoriteController;
use App\Domain\Organizations\Controllers\OrganizationController;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Controllers\PaymentController;
use App\Domain\Tickets\Controllers\TicketController;
use App\Domain\Tickets\Controllers\TicketScannerController;
use App\Domain\Venues\Controllers\VenueController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Shared\Enums\RoleEnum;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [EventController::class, 'index'])->name('home');
Route::get('events/search', [EventController::class, 'search'])->name('events.search');
Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');
Route::get('events/{event:slug}/sessions/{session}/book', [BookingController::class, 'selectSeats'])->name('events.sessions.book');

Route::middleware('auth')->group(function () {
    // Profile management (Breeze Inertia, not Fortify)
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password update
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Event management
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    // Organization event management (Event Manager, Org Admin, Org Owner)
    Route::prefix('org')->name('org.')->middleware([
        'role:'.RoleEnum::EventManager->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value,
    ])->group(function () {
        Route::get('events', [EventController::class, 'indexForOrg'])->name('events.index');
        Route::get('events/create', [EventController::class, 'create'])->name('events.create');
        Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::post('events/{event}/status', [EventController::class, 'toggleStatus'])->name('events.toggle-status');
    });

    // Organization analytics / reports (Event Manager, Finance Manager, Org Admin, Org Owner)
    Route::get('org/reports', fn () => Inertia::render('Org/Reports'))
        ->name('org.reports')
        ->middleware('role:'.RoleEnum::EventManager->value.','.RoleEnum::FinanceManager->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value);

    // Waiting list
    Route::post('events/{event}/waiting-list', [EventController::class, 'joinWaitingList'])->name('events.waiting-list.join');
    Route::delete('events/{event}/waiting-list', [EventController::class, 'leaveWaitingList'])->name('events.waiting-list.leave');

    // Venues
    Route::resource('venues', VenueController::class)->except(['create', 'edit']);
    Route::post('venues/{venue}/duplicate', [VenueController::class, 'duplicate'])->name('venues.duplicate');

    // Booking — hold seats / GA tickets
    Route::post('sessions/{session}/hold', [BookingController::class, 'holdSeats'])->name('sessions.hold');
    Route::get('checkout/review', [BookingController::class, 'review'])->name('checkout.review');
    Route::post('checkout', [BookingController::class, 'checkout'])->name('checkout.process');

    // Payment processing
    Route::post('checkout/pay', [PaymentController::class, 'processPayment'])->name('checkout.pay');
    Route::get('payments/history', [PaymentController::class, 'history'])->name('payments.history');
    Route::post('bookings/{booking}/refund-request', [PaymentController::class, 'refundRequest'])->name('bookings.refund-request');

    // Finance manager — refund management
    Route::get('payments/pending-refunds', [PaymentController::class, 'pendingRefunds'])->name('payments.pending-refunds');
    Route::post('payments/refunds/{refundRequest}/approve', [PaymentController::class, 'approveRefund'])->name('payments.refunds.approve');
    Route::post('payments/refunds/{refundRequest}/reject', [PaymentController::class, 'rejectRefund'])->name('payments.refunds.reject');

    // My bookings
    Route::get('bookings', [BookingController::class, 'myBookings'])->name('bookings.index');
    Route::get('bookings/{reference}', [BookingController::class, 'showBooking'])->name('bookings.show');

    // My tickets
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}/qr', [TicketController::class, 'qr'])->name('tickets.qr');

    // Favorites
    Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('events/{event}/favorite', [FavoriteController::class, 'store'])->name('events.favorite');
    Route::delete('events/{event}/favorite', [FavoriteController::class, 'destroy'])->name('events.unfavorite');

    // Dashboard (role-enforced)
    Route::get('dashboard', fn () => Inertia::render('Dashboards/DashboardRouter'))
        ->name('dashboard')
        ->middleware('role:'.RoleEnum::Customer->value.','.RoleEnum::TicketScanner->value.','.RoleEnum::SupportAgent->value.','.RoleEnum::EventManager->value.','.RoleEnum::FinanceManager->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value.','.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);

    // Ticket Scanner
    Route::get('scanner', [TicketScannerController::class, 'scanner'])
        ->name('scanner')
        ->middleware('role:'.RoleEnum::TicketScanner->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value.','.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);
    Route::post('scanner/scan', [TicketScannerController::class, 'scan'])
        ->name('scanner.scan')
        ->middleware('role:'.RoleEnum::TicketScanner->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value.','.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);
    Route::post('scanner/manual-checkin', [TicketScannerController::class, 'manualCheckIn'])
        ->name('scanner.manual-checkin')
        ->middleware('role:'.RoleEnum::TicketScanner->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value.','.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);
    Route::get('scanner/sessions/{session}/tickets', [TicketScannerController::class, 'sessionTickets'])
        ->name('scanner.session-tickets')
        ->middleware('role:'.RoleEnum::TicketScanner->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value.','.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);

    // Dashboard data API (role-enforced JSON)
    Route::get('dashboard/data/org', [DashboardDataController::class, 'orgDashboard'])
        ->name('dashboard.data.org')
        ->middleware('role:'.RoleEnum::EventManager->value.','.RoleEnum::FinanceManager->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value);
    Route::get('dashboard/data/finance', [DashboardDataController::class, 'financeDashboard'])
        ->name('dashboard.data.finance')
        ->middleware('role:'.RoleEnum::FinanceManager->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value);
    Route::get('dashboard/data/scanner', [DashboardDataController::class, 'scannerDashboard'])
        ->name('dashboard.data.scanner')
        ->middleware('role:'.RoleEnum::TicketScanner->value.','.RoleEnum::OrganizationAdmin->value.','.RoleEnum::OrganizationOwner->value.','.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);
    Route::get('dashboard/data/customer', [DashboardDataController::class, 'customerDashboard'])
        ->name('dashboard.data.customer')
        ->middleware('role:'.RoleEnum::Customer->value);
    Route::get('dashboard/data/platform', [DashboardDataController::class, 'platformDashboard'])
        ->name('dashboard.data.platform')
        ->middleware('role:'.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value);
    Route::get('dashboard/data/super-admin', [DashboardDataController::class, 'superAdminDashboard'])
        ->name('dashboard.data.super-admin')
        ->middleware('role:'.RoleEnum::SuperAdministrator->value);

    // Platform Admin — manage orgs
    Route::prefix('admin')->name('admin.')->middleware(['role:'.RoleEnum::PlatformAdmin->value.','.RoleEnum::SuperAdministrator->value])->group(function () {
        Route::get('organizations', [AdminOrganizationController::class, 'index'])->name('organizations');
        Route::post('organizations/{organization}/toggle-status', [AdminOrganizationController::class, 'toggleStatus'])->name('organizations.toggle-status');
        Route::get('users', [AdminUserController::class, 'index'])->name('users');
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('system/health', [SystemController::class, 'health'])->name('system.health');
        Route::post('system/clear-cache', [SystemController::class, 'clearCache'])->name('system.clear-cache');
        Route::post('system/toggle-maintenance', [SystemController::class, 'toggleMaintenance'])->name('system.toggle-maintenance');

        // CMS (blog, FAQ, sponsors)
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::get('blog', [CmsController::class, 'blogIndex'])->name('blog');
            Route::post('blog', [CmsController::class, 'blogStore']);
            Route::put('blog/{post}', [CmsController::class, 'blogUpdate'])->name('blog.update');
            Route::delete('blog/{post}', [CmsController::class, 'blogDestroy'])->name('blog.destroy');
            Route::get('faq', [CmsController::class, 'faqIndex'])->name('faq');
            Route::post('faq', [CmsController::class, 'faqStore']);
            Route::put('faq/{item}', [CmsController::class, 'faqUpdate'])->name('faq.update');
            Route::delete('faq/{item}', [CmsController::class, 'faqDestroy'])->name('faq.destroy');
            Route::get('sponsors', [CmsController::class, 'sponsorIndex'])->name('sponsors');
            Route::post('sponsors', [CmsController::class, 'sponsorStore']);
            Route::put('sponsors/{sponsor}', [CmsController::class, 'sponsorUpdate'])->name('sponsors.update');
            Route::delete('sponsors/{sponsor}', [CmsController::class, 'sponsorDestroy'])->name('sponsors.destroy');
        });
    });

    // Organization switching
    Route::put('organizations/switch/{organization}', function (Organization $organization) {
        $user = request()->user();

        if (! $organization->hasUser($user) && ! $user->isPlatformAdmin() && ! $user->isSuperAdmin()) {
            abort(403);
        }

        $user->switchOrganization($organization);
        session()->put('current_organization_id', $organization->id);

        return redirect()->back();
    })->name('organizations.switch');

    // Organization profile & settings
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::delete('invitations/{invitation}', [OrganizationController::class, 'cancelInvitation'])
            ->name('invitations.cancel');

        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::get('{organization}/settings', [OrganizationController::class, 'settings'])->name('settings');
        Route::put('{organization}', [OrganizationController::class, 'update'])->name('update');
        Route::delete('{organization}', [OrganizationController::class, 'destroy'])->name('destroy');

        Route::post('{organization}/invitations', [OrganizationController::class, 'inviteStaff'])->name('invitations.store');
        Route::put('{organization}/staff/{staff}/role', [OrganizationController::class, 'updateStaffRole'])->name('staff.role');
        Route::delete('{organization}/staff/{staff}', [OrganizationController::class, 'removeStaff'])->name('staff.remove');
    });

    Route::get('invitations/{token}/accept', [OrganizationController::class, 'acceptInvitation'])
        ->name('organizations.invitations.accept');
});
