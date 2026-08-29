<?php

declare(strict_types=1);

namespace App\Domain\Payments\Controllers;

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Payments\Models\RefundRequest;
use App\Domain\Payments\Requests\ProcessPaymentRequest;
use App\Domain\Payments\Services\PaymentGatewayManager;
use App\Domain\Payments\Services\RefundWorkflow;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function processPayment(ProcessPaymentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $holdIds = $request->input('hold_ids', []);
        $gatewayName = $request->input('gateway', config('services.payment.gateway', 'stripe'));
        $gateway = $this->gatewayManager->driver($gatewayName);

        try {
            $booking = $this->bookingService->checkoutWithPayment(
                user: $user,
                holdIds: $holdIds,
                gateway: $gateway,
                paymentParams: $request->input('payment', []),
                couponCode: $request->input('coupon_code'),
                giftCardCode: $request->input('gift_card_code'),
            );

            return Redirect::route('bookings.show', $booking->reference)
                ->with('success', 'Payment successful! Your booking is confirmed.');
        } catch (\RuntimeException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function history(Request $request): Response
    {
        $user = $request->user();

        $bookings = Booking::where('user_id', $user->id)
            ->with(['eventSession.event', 'transactions', 'invoices'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Payments/History', [
            'bookings' => $bookings,
        ]);
    }

    public function refundRequest(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($booking->user_id !== $request->user()->id) {
            abort(403);
        }

        $gateway = $this->gatewayManager->driver();
        $workflow = new RefundWorkflow($gateway);

        try {
            $workflow->requestRefund(
                booking: $booking,
                user: $request->user(),
                amount: (float) $request->input('amount'),
                reason: $request->input('reason'),
                notes: $request->input('notes'),
            );

            return Redirect::back()->with('success', 'Refund request submitted. A finance manager will review it.');
        } catch (\RuntimeException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function pendingRefunds(Request $request): Response
    {
        $orgId = $this->authorizeRefundManager($request);

        $refunds = RefundRequest::with(['booking.eventSession.event', 'requestedBy'])
            ->when($orgId !== null, fn ($query) => $query->whereHas(
                'booking.eventSession.event',
                fn ($q) => $q->where('organization_id', $orgId),
            ))
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return Inertia::render('Payments/PendingRefunds', [
            'refunds' => $refunds,
        ]);
    }

    public function approveRefund(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $this->authorizeRefundManager($request);
        $this->assertRefundInScope($request, $refundRequest);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $gateway = $this->gatewayManager->driver();
        $workflow = new RefundWorkflow($gateway);

        try {
            $workflow->approve($refundRequest, $request->user());

            return Redirect::back()->with('success', 'Refund approved and processed.');
        } catch (\RuntimeException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function rejectRefund(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $this->authorizeRefundManager($request);
        $this->assertRefundInScope($request, $refundRequest);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $gateway = $this->gatewayManager->driver();
        $workflow = new RefundWorkflow($gateway);

        $workflow->reject(
            $refundRequest,
            $request->user(),
            $request->input('notes'),
        );

        return Redirect::back()->with('success', 'Refund request rejected.');
    }

    /**
     * Allow org-scoped finance roles and platform admins to manage refunds.
     * Returns the organization id for org-scoped users, or null for platform roles.
     */
    private function authorizeRefundManager(Request $request): ?int
    {
        $user = $request->user();
        $orgId = $user->currentOrganizationId();

        app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);
        $user->unsetRelation('roles');

        if ($user->hasAnyRole(['FinanceManager', 'OrganizationAdmin', 'OrganizationOwner'])) {
            return $orgId;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $user->unsetRelation('roles');

        if ($user->hasAnyRole(['PlatformAdmin', 'SuperAdministrator'])) {
            return null;
        }

        throw new AccessDeniedHttpException('You are not authorized to manage refunds.');
    }

    private function assertRefundInScope(Request $request, RefundRequest $refundRequest): void
    {
        $orgId = $request->user()->currentOrganizationId();

        if ($orgId === null) {
            return;
        }

        $refundOrgId = $refundRequest->booking?->eventSession?->event?->organization_id;

        if ((int) $refundOrgId !== $orgId) {
            throw new AccessDeniedHttpException('This refund does not belong to your organization.');
        }
    }
}
