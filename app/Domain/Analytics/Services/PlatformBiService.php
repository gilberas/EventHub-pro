<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\DatabaseHelper;
use App\Shared\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

/**
 * Platform-wide Business Intelligence — cross-tenant queries.
 *
 * INTENTIONAL DESIGN DECISION: These queries deliberately bypass organization scoping.
 * They are ONLY callable by SuperAdministrator role and provide aggregate platform-level
 * metrics that inherently span all tenants (e.g., total platform revenue, organizer rankings).
 * Access is enforced at the controller/route level via CheckRole or Gate.
 */
class PlatformBiService
{
    /**
     * @return array{total_revenue: float, total_bookings: int, total_fees: float, avg_order: float}
     */
    public function platformRevenue(): array
    {
        $data = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::PartiallyRefunded])
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->selectRaw('COUNT(*) as total_bookings')
            ->selectRaw('COALESCE(SUM(fees), 0) as total_fees')
            ->first();

        $revenue = (float) ($data->total_revenue ?? 0);
        $count = (int) ($data->total_bookings ?? 0);

        return [
            'total_revenue' => $revenue,
            'total_bookings' => $count,
            'total_fees' => (float) ($data->total_fees ?? 0),
            'avg_order' => $count > 0 ? round($revenue / $count, 2) : 0,
        ];
    }

    /**
     * Organizer performance leaderboard — cross-tenant.
     *
     * @return array<int, array{org_id: int, name: string, total_events: int, total_revenue: float, total_bookings: int}>
     */
    public function organizerLeaderboard(int $limit = 10): array
    {
        return Organization::query()
            ->select([
                'organizations.id as org_id',
                'organizations.name',
                DB::raw('COALESCE(evt.event_count, 0) as total_events'),
                DB::raw('COALESCE(rev.total_revenue, 0) as total_revenue'),
                DB::raw('COALESCE(rev.booking_count, 0) as total_bookings'),
            ])
            ->leftJoin(DB::raw('(SELECT e.organization_id, COUNT(*) as event_count FROM events e GROUP BY e.organization_id) as evt'), 'evt.organization_id', '=', 'organizations.id')
            ->leftJoin(DB::raw('(SELECT e2.organization_id, COALESCE(SUM(b.total), 0) as total_revenue, COUNT(b.id) as booking_count FROM bookings b INNER JOIN event_sessions es ON es.id = b.event_session_id INNER JOIN events e2 ON e2.id = es.event_id WHERE b.status IN (\''.BookingStatus::Confirmed->value.'\', \''.BookingStatus::PartiallyRefunded->value.'\') GROUP BY e2.organization_id) as rev'), 'rev.organization_id', '=', 'organizations.id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * @return array{total_platform_users: int, new_this_month: int, growth_percent: float}
     */
    public function platformCustomerGrowth(): array
    {
        $total = User::count();
        $newThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = User::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->startOfMonth()])->count();

        return [
            'total_platform_users' => $total,
            'new_this_month' => $newThisMonth,
            'growth_percent' => $lastMonth > 0 ? round((($newThisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0,
        ];
    }

    /**
     * @return array{total_orgs: int, active_orgs: int, suspended_orgs: int}
     */
    public function platformOrgSummary(): array
    {
        return [
            'total_orgs' => Organization::count(),
            'active_orgs' => Organization::where('is_active', true)->count(),
            'suspended_orgs' => Organization::where('is_active', false)->count(),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    public function monthlyPlatformRevenue(int $months = 6): array
    {
        $dateExpr = DatabaseHelper::dateFormat('created_at');
        $raw = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::PartiallyRefunded])
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
}
