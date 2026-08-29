<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Controllers;

use App\Domain\Analytics\Services\PlatformBiService;
use App\Domain\Analytics\Services\ReportingService;
use App\Domain\Bookings\Models\Booking;
use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Models\Invoice;
use App\Domain\Payments\Models\RefundRequest;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketScanLog;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\RefundStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class DashboardDataController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting,
        private readonly PlatformBiService $platformBi,
    ) {}

    public function orgDashboard(Request $request): JsonResponse
    {
        $orgId = $request->user()->currentOrganizationId();

        if (! $orgId) {
            return response()->json(['error' => 'No organization selected'], 400);
        }

        $org = Organization::findOrFail($orgId);
        $data = $this->reporting->revenueSummary($org);
        $attendance = $this->reporting->attendanceSummary($org);
        $refunds = $this->reporting->refundSummary($org);
        $growth = $this->reporting->customerGrowth($org);
        $overview = $this->reporting->orgOverview($org);
        $popular = $this->reporting->popularEvents($org);
        $monthly = $this->reporting->monthlyRevenue($org);
        $salesByEvent = $this->reporting->salesByEvent($org);
        $upcoming = $this->reporting->upcomingEvents($org);
        $staffCount = $org->staffCount();

        return response()->json([
            'revenue' => $data,
            'attendance' => $attendance,
            'refunds' => $refunds,
            'customer_growth' => $growth,
            'overview' => $overview,
            'popular_events' => $popular,
            'upcoming_events' => $upcoming,
            'monthly_revenue' => $monthly,
            'sales_by_event' => $salesByEvent,
            'staff_count' => $staffCount,
            'org_id' => $org->id,
            'org_name' => $org->name,
            'org_currency' => $org->currency,
        ]);
    }

    public function financeDashboard(Request $request): JsonResponse
    {
        $orgId = $request->user()->currentOrganizationId();

        if (! $orgId) {
            return response()->json(['error' => 'No organization selected'], 400);
        }

        $org = Organization::findOrFail($orgId);
        $revenue = $this->reporting->revenueSummary($org);
        $refunds = $this->reporting->refundSummary($org);

        $recentTransactions = Booking::whereHas('eventSession.event', fn ($q) => $q->where('organization_id', $orgId))
            ->with(['eventSession.event', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->reference,
                'event' => $b->eventSession?->event?->title ?? 'N/A',
                'customer' => $b->user?->name ?? 'N/A',
                'amount' => (float) $b->total,
                'status' => $b->status->value,
                'created_at' => $b->created_at,
            ]);

        $pendingRefunds = RefundRequest::whereHas('booking.eventSession.event', fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', RefundStatus::Pending)
            ->count();

        $invoiceCount = Invoice::whereHas('booking.eventSession.event', fn ($q) => $q->where('organization_id', $orgId))->count();

        return response()->json([
            'revenue' => $revenue,
            'refunds' => $refunds,
            'pending_refunds' => $pendingRefunds,
            'invoice_count' => $invoiceCount,
            'recent_transactions' => $recentTransactions,
            'org_currency' => $org->currency,
        ]);
    }

    public function scannerDashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $scannedToday = TicketScanLog::where('scanned_by_user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $validToday = TicketScanLog::where('scanned_by_user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('result', 'valid')
            ->count();

        return response()->json([
            'scanned_today' => $scannedToday,
            'valid_today' => $validToday,
            'recent_scans' => TicketScanLog::with(['ticket.booking.user', 'ticket.bookingItem.ticketType'])
                ->where('scanned_by_user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'result' => $log->result,
                    'holder_name' => $log->ticket?->booking?->user?->name ?? 'N/A',
                    'ticket_type' => $log->ticket?->bookingItem?->ticketType?->name ?? 'N/A',
                    'time' => $log->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function platformDashboard(Request $request): JsonResponse
    {
        $revenue = $this->platformBi->platformRevenue();
        $orgs = $this->platformBi->platformOrgSummary();
        $growth = $this->platformBi->platformCustomerGrowth();
        $leaderboard = $this->platformBi->organizerLeaderboard(5);
        $monthlyRevenue = $this->platformBi->monthlyPlatformRevenue();

        $totalEvents = Event::count();
        $totalTicketsSold = Ticket::where('status', 'used')->count();

        return response()->json([
            'revenue' => $revenue,
            'organizations' => $orgs,
            'customer_growth' => $growth,
            'leaderboard' => $leaderboard,
            'monthly_revenue' => $monthlyRevenue,
            'total_events' => $totalEvents,
            'total_tickets_sold' => $totalTicketsSold,
        ]);
    }

    public function superAdminDashboard(Request $request): JsonResponse
    {
        $platform = $this->platformDashboard($request);

        $failedLogins = Audit::where('event', 'login_failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $eventsThisMonth = Event::where('created_at', '>=', now()->startOfMonth())->count();
        $activeSessions = User::where('created_at', '>=', now()->subHours(2))->count();

        $systemHealth = [
            'web_server' => ['status' => 'Operational', 'load' => '42%'],
            'database' => ['status' => 'Operational', 'load' => '28%'],
            'cache' => ['status' => 'Degraded', 'load' => '76%'],
            'queue' => ['status' => 'Operational', 'load' => '34%'],
            'storage' => ['status' => 'Operational', 'load' => '52%'],
        ];

        return response()->json([
            'platform' => $platform->original,
            'failed_logins_24h' => $failedLogins,
            'events_this_month' => $eventsThisMonth,
            'active_sessions_2h' => $activeSessions,
            'system_health' => $systemHealth,
            'maintenance_mode' => app()->isDownForMaintenance(),
        ]);
    }

    public function customerDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $upcoming = Booking::where('user_id', $user->id)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::PendingPayment])
            ->with('eventSession.event')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'reference' => $b->reference,
                'event' => $b->eventSession?->event?->title ?? 'N/A',
                'date' => $b->eventSession?->start_date?->format('M j, Y') ?? 'N/A',
                'status' => $b->status->value,
                'amount' => (float) $b->total,
            ]);

        $ticketCount = Ticket::whereHas('booking', fn ($q) => $q->where('user_id', $user->id))->count();
        $activeBookings = Booking::where('user_id', $user->id)->where('status', BookingStatus::Confirmed)->count();
        $pendingBookings = Booking::where('user_id', $user->id)->where('status', BookingStatus::PendingPayment)->count();

        return response()->json([
            'upcoming_events' => $upcoming->count(),
            'active_orders' => $activeBookings,
            'ticket_count' => $ticketCount,
            'pending_bookings' => $pendingBookings,
            'recent_orders' => $upcoming,
        ]);
    }
}
