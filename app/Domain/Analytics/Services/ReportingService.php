<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use App\Shared\DatabaseHelper;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\EventStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * All report methods use database aggregation (SUM, COUNT) — no rows pulled into PHP.
 * If in-PHP aggregation is ever needed, flag it as a known scaling limitation for Phase 14.
 */
class ReportingService
{
    /**
     * @return array{total_revenue: float, booking_count: int, avg_order_value: float, fees_collected: float}
     */
    public function revenueSummary(Organization $organization): array
    {
        $data = Booking::whereHas('eventSession.event', function ($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Refunded, BookingStatus::PartiallyRefunded])
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->selectRaw('COUNT(*) as booking_count')
            ->selectRaw('COALESCE(SUM(fees), 0) as fees_collected')
            ->first();

        $totalRevenue = (float) ($data->total_revenue ?? 0);
        $count = (int) ($data->booking_count ?? 0);

        return [
            'total_revenue' => $totalRevenue,
            'booking_count' => $count,
            'avg_order_value' => $count > 0 ? round($totalRevenue / $count, 2) : 0,
            'fees_collected' => (float) ($data->fees_collected ?? 0),
        ];
    }

    /**
     * @return array{total: int, checked_in: int, rate: float}
     */
    public function attendanceSummary(Organization $organization): array
    {
        $sessionIds = EventSession::whereHas('event', fn ($q) => $q->where('organization_id', $organization->id))
            ->pluck('id');

        $totalTickets = Ticket::whereIn('event_session_id', $sessionIds)->count();
        $checkedIn = Ticket::whereIn('event_session_id', $sessionIds)->whereNotNull('checked_in_at')->count();

        return [
            'total' => $totalTickets,
            'checked_in' => $checkedIn,
            'rate' => $totalTickets > 0 ? round(($checkedIn / $totalTickets) * 100, 1) : 0,
        ];
    }

    /**
     * @return array{total_refunds: float, refund_count: int, refund_rate: float}
     */
    public function refundSummary(Organization $organization): array
    {
        $refundedBookings = Booking::whereHas('eventSession.event', fn ($q) => $q->where('organization_id', $organization->id))
            ->whereIn('status', [BookingStatus::Refunded, BookingStatus::PartiallyRefunded])
            ->selectRaw('COUNT(*) as refund_count, COALESCE(SUM(total), 0) as refunded_total')
            ->first();

        $totalRevenue = $this->revenueSummary($organization)['total_revenue'];
        $refundedTotal = (float) ($refundedBookings->refunded_total ?? 0);
        $refundCount = (int) ($refundedBookings->refund_count ?? 0);

        return [
            'total_refunds' => $refundedTotal,
            'refund_count' => $refundCount,
            'refund_rate' => $totalRevenue > 0 ? round(($refundedTotal / $totalRevenue) * 100, 1) : 0,
        ];
    }

    /**
     * @return array{total_customers: int, new_this_month: int, growth_percent: float}
     */
    public function customerGrowth(Organization $organization): array
    {
        $totalCustomers = User::whereHas('organizations', fn ($q) => $q->where('organization_id', $organization->id))
            ->count();

        $newThisMonth = User::whereHas('organizations', fn ($q) => $q->where('organization_id', $organization->id))
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $lastMonth = User::whereHas('organizations', fn ($q) => $q->where('organization_id', $organization->id))
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->startOfMonth()])
            ->count();

        $growthPercent = $lastMonth > 0 ? round((($newThisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        return [
            'total_customers' => $totalCustomers,
            'new_this_month' => $newThisMonth,
            'growth_percent' => $growthPercent,
        ];
    }

    /**
     * @return array<int, array{event_id: int, title: string, total_revenue: float, tickets_sold: int}>
     */
    public function popularEvents(Organization $organization, int $limit = 5): array
    {
        return Event::where('events.organization_id', $organization->id)
            ->select([
                'events.id as event_id',
                'events.title',
                DB::raw('COALESCE(SUM(bookings.total), 0) as total_revenue'),
                DB::raw('COUNT(DISTINCT bookings.id) as tickets_sold'),
            ])
            ->join('event_sessions', 'event_sessions.event_id', '=', 'events.id')
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.event_session_id', '=', 'event_sessions.id')
                    ->whereIn('bookings.status', [BookingStatus::Confirmed->value, BookingStatus::PartiallyRefunded->value]);
            })
            ->groupBy('events.id', 'events.title')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * @return array<string, float|int>
     */
    public function monthlyRevenue(Organization $organization, int $months = 6): array
    {
        $dateExpr = DatabaseHelper::dateFormat('created_at');
        $raw = Booking::whereHas('eventSession.event', fn ($q) => $q->where('organization_id', $organization->id))
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::PartiallyRefunded])
            ->where('created_at', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("{$dateExpr} as month, COALESCE(SUM(total), 0) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $result[$month] = (float) ($raw[$month] ?? 0);
        }

        return $result;
    }

    /**
     * @return list<array{event_id: int, title: string, status: string, next_session: string|null, tickets_sold: int}>
     */
    public function upcomingEvents(Organization $organization, int $limit = 5): array
    {
        return Event::query()
            ->where('events.organization_id', $organization->id)
            ->where('events.status', EventStatus::Published)
            ->join('event_sessions', 'event_sessions.event_id', '=', 'events.id')
            ->where('event_sessions.start_date', '>', now())
            ->select([
                'events.id as event_id',
                'events.title',
                'events.status',
                DB::raw('MIN(event_sessions.start_date) as next_session'),
                DB::raw('COUNT(DISTINCT bookings.id) as tickets_sold'),
            ])
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.event_session_id', '=', 'event_sessions.id')
                    ->whereIn('bookings.status', [BookingStatus::Confirmed->value, BookingStatus::PendingPayment->value]);
            })
            ->groupBy('events.id', 'events.title', 'events.status')
            ->orderBy('next_session')
            ->limit($limit)
            ->get()
            ->map(fn ($event): array => [
                'event_id' => (int) $event->event_id,
                'title' => $event->title,
                'status' => $event->status,
                'next_session' => $event->next_session ? Carbon::parse($event->next_session)->format('M j, Y') : null,
                'tickets_sold' => (int) $event->tickets_sold,
            ])
            ->all();
    }

    /**
     * @return array{total_events: int, active_events: int, total_venues: int, total_ticket_types: int}
     */
    public function orgOverview(Organization $organization): array
    {
        return [
            'total_events' => Event::where('organization_id', $organization->id)->count(),
            'active_events' => Event::where('organization_id', $organization->id)
                ->where('status', EventStatus::Published)
                ->count(),
            'total_venues' => Venue::where('organization_id', $organization->id)->count(),
            'total_ticket_types' => TicketType::whereHas('eventSession.event', fn ($q) => $q->where('organization_id', $organization->id))->count(),
        ];
    }

    /**
     * @return array{total_revenue: float, booking_count: int, fees: float, refunds: float}
     */
    public function salesByEvent(Organization $organization): array
    {
        $confirmed = Booking::whereHas('eventSession.event', fn ($q) => $q->where('organization_id', $organization->id))
            ->where('status', BookingStatus::Confirmed)
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COALESCE(SUM(fees), 0) as fees, COUNT(*) as count')
            ->first();

        $refunds = Booking::whereHas('eventSession.event', fn ($q) => $q->where('organization_id', $organization->id))
            ->whereIn('status', [BookingStatus::Refunded, BookingStatus::PartiallyRefunded])
            ->sum('total');

        return [
            'total_revenue' => (float) ($confirmed->revenue ?? 0),
            'booking_count' => (int) ($confirmed->count ?? 0),
            'fees' => (float) ($confirmed->fees ?? 0),
            'refunds' => (float) ($refunds ?? 0),
        ];
    }
}
