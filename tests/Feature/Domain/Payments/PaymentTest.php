<?php

declare(strict_types=1);

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Bookings\Services\BookingService;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTOs\PaymentResult;
use App\Domain\Payments\Models\Coupon;
use App\Domain\Payments\Models\GiftCard;
use App\Domain\Payments\Models\PaymentTransaction;
use App\Domain\Payments\Services\CouponService;
use App\Domain\Payments\Services\GiftCardService;
use App\Domain\Payments\Services\RefundWorkflow;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\CouponType;
use App\Shared\Enums\RefundStatus;
use App\Shared\Enums\TransactionType;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --------------- Coupon Service Tests ---------------

it('validates a valid percentage coupon', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->percentage()->create([
        'organization_id' => $org->id,
        'value' => 20,
        'max_discount' => 50,
    ]);

    $service = app(CouponService::class);
    $result = $service->validateCoupon($coupon, 200);

    expect($result['valid'])->toBeTrue();
    expect($result['discount'])->toBe(40.0);
});

it('validates a valid fixed coupon', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->fixed()->create([
        'organization_id' => $org->id,
        'value' => 25,
    ]);

    $service = app(CouponService::class);
    $result = $service->validateCoupon($coupon, 200);

    expect($result['valid'])->toBeTrue();
    expect($result['discount'])->toBe(25.0);
});

it('rejects a coupon that has exceeded max uses', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->exhausted()->create([
        'organization_id' => $org->id,
    ]);

    $service = app(CouponService::class);
    $result = $service->validateCoupon($coupon, 100);

    expect($result['valid'])->toBeFalse();
});

it('rejects an expired coupon', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->expired()->create([
        'organization_id' => $org->id,
    ]);

    $service = app(CouponService::class);
    $result = $service->validateCoupon($coupon, 100);

    expect($result['valid'])->toBeFalse();
});

it('rejects a coupon when order amount is below minimum', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->create([
        'organization_id' => $org->id,
        'min_order_amount' => 100,
        'value' => 10,
    ]);

    $service = app(CouponService::class);
    $result = $service->validateCoupon($coupon, 50);

    expect($result['valid'])->toBeFalse();
});

it('respects max discount cap for percentage coupons', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->percentage()->create([
        'organization_id' => $org->id,
        'value' => 50,
        'max_discount' => 30,
    ]);

    $service = app(CouponService::class);
    $result = $service->validateCoupon($coupon, 200);

    expect($result['valid'])->toBeTrue();
    expect($result['discount'])->toBe(30.0);
});

it('applies coupon atomically preventing overshoot', function () {
    $org = Organization::factory()->create();
    $coupon = Coupon::factory()->create([
        'organization_id' => $org->id,
        'max_uses' => 1,
        'current_uses' => 0,
        'value' => 10,
        'type' => CouponType::Fixed,
    ]);

    $service = app(CouponService::class);

    $discount1 = $service->apply($coupon, 100);
    expect($discount1)->toBe(10.0);

    expect(fn () => $service->apply($coupon->fresh(), 100))
        ->toThrow(RuntimeException::class, 'Coupon is no longer valid.');
});

// --------------- Gift Card Service Tests ---------------

it('validates a gift card with sufficient balance', function () {
    $org = Organization::factory()->create();
    $giftCard = GiftCard::factory()->create([
        'organization_id' => $org->id,
        'current_balance' => 100,
        'original_balance' => 100,
    ]);

    $service = app(GiftCardService::class);
    $result = $service->validateGiftCard($giftCard, 50);

    expect($result['valid'])->toBeTrue();
    expect($result['amount_to_use'])->toBe(50.0);
    expect($result['remaining_balance'])->toBe(50.0);
});

it('validates a gift card with insufficient balance uses remaining', function () {
    $org = Organization::factory()->create();
    $giftCard = GiftCard::factory()->create([
        'organization_id' => $org->id,
        'current_balance' => 30,
        'original_balance' => 100,
    ]);

    $service = app(GiftCardService::class);
    $result = $service->validateGiftCard($giftCard, 100);

    expect($result['valid'])->toBeTrue();
    expect($result['amount_to_use'])->toBe(30.0);
    expect($result['remaining_balance'])->toBe(0.0);
});

it('rejects an exhausted gift card', function () {
    $org = Organization::factory()->create();
    $giftCard = GiftCard::factory()->exhausted()->create([
        'organization_id' => $org->id,
    ]);

    $service = app(GiftCardService::class);
    $result = $service->validateGiftCard($giftCard, 50);

    expect($result['valid'])->toBeFalse();
});

it('redeems gift card atomically', function () {
    $org = Organization::factory()->create();
    $giftCard = GiftCard::factory()->create([
        'organization_id' => $org->id,
        'current_balance' => 100,
        'original_balance' => 100,
    ]);

    $service = app(GiftCardService::class);

    $used = $service->redeem($giftCard, 40);
    expect($used)->toBe(40.0);
    expect((float) $giftCard->fresh()->current_balance)->toBe(60.0);
});

it('prevents redeeming more than gift card balance', function () {
    $org = Organization::factory()->create();
    $giftCard = GiftCard::factory()->create([
        'organization_id' => $org->id,
        'current_balance' => 10,
        'original_balance' => 100,
    ]);

    $service = app(GiftCardService::class);

    $used = $service->redeem($giftCard, 50);
    expect($used)->toBe(10.0);
    expect((float) $giftCard->fresh()->current_balance)->toBe(0.0);
});

// --------------- Refund Workflow Tests ---------------

it('creates a refund request with pending status', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'total' => 100,
        'status' => BookingStatus::Confirmed,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $workflow = new RefundWorkflow($gateway);

    $refund = $workflow->requestRefund($booking, $user, 100, 'Item not as described');

    expect($refund->status)->toBe(RefundStatus::Pending);
    expect((float) $refund->amount)->toBe(100.0);
    expect($refund->reason)->toBe('Item not as described');
});

it('rejects refund request that is not pending', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'total' => 100,
        'status' => BookingStatus::Confirmed,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');
    $workflow = new RefundWorkflow($gateway);

    $refund = $workflow->requestRefund($booking, $user, 100, 'Test');
    $refund->update(['status' => RefundStatus::Rejected]);

    expect(fn () => $workflow->approve($refund->fresh(), $reviewer))
        ->toThrow(RuntimeException::class, 'Refund request has already been processed.');
});

it('approves refund and calls gateway and updates booking status', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'total' => 100,
        'status' => BookingStatus::Confirmed,
    ]);

    PaymentTransaction::create([
        'payable_type' => get_class($booking),
        'payable_id' => $booking->id,
        'gateway' => 'stripe',
        'transaction_id' => 'pi_test_123',
        'type' => TransactionType::Charge,
        'amount' => 100,
        'currency' => 'USD',
        'status' => 'succeeded',
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');
    $gateway->shouldReceive('refund')
        ->once()
        ->with('pi_test_123', 100.0)
        ->andReturn(new PaymentResult(true, 'rf_test_123', amountRefunded: 100));

    $workflow = new RefundWorkflow($gateway);
    $refund = $workflow->requestRefund($booking, $user, 100, 'Test');
    $result = $workflow->approve($refund, $reviewer);

    expect($result->status)->toBe(RefundStatus::Refunded);
    expect($result->reviewed_by_user_id)->toBe($reviewer->id);
    expect($result->booking->fresh()->status)->toBe(BookingStatus::Refunded);
});

it('records a partial refund transaction', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'total' => 100,
        'status' => BookingStatus::Confirmed,
    ]);

    PaymentTransaction::create([
        'payable_type' => get_class($booking),
        'payable_id' => $booking->id,
        'gateway' => 'stripe',
        'transaction_id' => 'pi_test_456',
        'type' => TransactionType::Charge,
        'amount' => 100,
        'currency' => 'USD',
        'status' => 'succeeded',
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');
    $gateway->shouldReceive('refund')
        ->once()
        ->with('pi_test_456', 30.0)
        ->andReturn(new PaymentResult(true, 'rf_partial_789', amountRefunded: 30));

    $workflow = new RefundWorkflow($gateway);
    $refund = $workflow->requestRefund($booking, $user, 30, 'Partial refund');
    $result = $workflow->approve($refund, $reviewer);

    expect($result->booking->fresh()->status)->toBe(BookingStatus::PartiallyRefunded);

    $tx = PaymentTransaction::where('payable_id', $booking->id)
        ->where('type', TransactionType::PartialRefund)
        ->first();
    expect($tx)->not->toBeNull();
    expect((float) $tx->amount)->toBe(30.0);
});

// --------------- BookingService Payment Integration Tests ---------------

it('processes payment successfully via gateway', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 50,
        'quantity_available' => 10,
    ]);

    $hold = DB::transaction(function () use ($session, $ticketType, $user) {
        return app(BookingService::class)->holdGATickets($session, $ticketType, $user, 2);
    });

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');
    $gateway->shouldReceive('charge')
        ->once()
        ->andReturn(new PaymentResult(true, 'pi_test_pay_1', amountCharged: 105.0));

    $booking = app(BookingService::class)->checkoutWithPayment(
        user: $user,
        holdIds: [$hold->id],
        gateway: $gateway,
        paymentParams: ['payment_method_id' => 'pm_test_123'],
    );

    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->paid_at)->not->toBeNull();

    $tx = $booking->transactions()->first();
    expect($tx)->not->toBeNull();
    expect($tx->status)->toBe('succeeded');
    expect($tx->gateway)->toBe('stripe');
});

it('fails payment and does not confirm booking', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 50,
        'quantity_available' => 10,
    ]);

    $hold = DB::transaction(function () use ($session, $ticketType, $user) {
        return app(BookingService::class)->holdGATickets($session, $ticketType, $user, 2);
    });

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');
    $gateway->shouldReceive('charge')
        ->once()
        ->andReturn(new PaymentResult(false, errorMessage: 'Card declined'));

    expect(fn () => app(BookingService::class)->checkoutWithPayment(
        user: $user,
        holdIds: [$hold->id],
        gateway: $gateway,
        paymentParams: ['payment_method_id' => 'pm_bad'],
    ))->toThrow(RuntimeException::class, 'Payment failed: Card declined');
});

it('applies coupon discount during checkout with payment', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 100,
        'quantity_available' => 10,
    ]);

    $coupon = Coupon::factory()->fixed()->create([
        'organization_id' => $org->id,
        'value' => 20,
        'code' => 'TEST20',
    ]);

    $hold = DB::transaction(function () use ($session, $ticketType, $user) {
        return app(BookingService::class)->holdGATickets($session, $ticketType, $user, 1);
    });

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');

    $expectedCharge = 100 + round(100 * 0.05, 2) - 20;

    $gateway->shouldReceive('charge')
        ->once()
        ->andReturnUsing(function ($params) use ($expectedCharge) {
            expect($params['amount'])->toBe($expectedCharge);

            return new PaymentResult(true, 'pi_test_coupon_1', amountCharged: $expectedCharge);
        });

    $booking = app(BookingService::class)->checkoutWithPayment(
        user: $user,
        holdIds: [$hold->id],
        gateway: $gateway,
        paymentParams: ['payment_method_id' => 'pm_test'],
        couponCode: 'TEST20',
    );

    expect($booking->status)->toBe(BookingStatus::Confirmed);

    $this->assertDatabaseHas('booking_coupons', [
        'booking_id' => $booking->id,
    ]);
});

it('applies gift card during checkout with payment', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $ticketType = TicketType::factory()->create([
        'event_session_id' => $session->id,
        'price' => 100,
        'quantity_available' => 10,
    ]);

    $giftCard = GiftCard::factory()->create([
        'organization_id' => $org->id,
        'current_balance' => 50,
        'original_balance' => 100,
        'code' => 'GC-TEST50',
    ]);

    $hold = DB::transaction(function () use ($session, $ticketType, $user) {
        return app(BookingService::class)->holdGATickets($session, $ticketType, $user, 1);
    });

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');

    $expectedCharge = 100 + round(100 * 0.05, 2) - 50;

    $gateway->shouldReceive('charge')
        ->once()
        ->andReturnUsing(function ($params) use ($expectedCharge) {
            expect($params['amount'])->toBe($expectedCharge);

            return new PaymentResult(true, 'pi_test_gc_1', amountCharged: $expectedCharge);
        });

    $booking = app(BookingService::class)->checkoutWithPayment(
        user: $user,
        holdIds: [$hold->id],
        gateway: $gateway,
        paymentParams: ['payment_method_id' => 'pm_test'],
        giftCardCode: 'GC-TEST50',
    );

    expect($booking->status)->toBe(BookingStatus::Confirmed);

    $this->assertDatabaseHas('booking_gift_cards', [
        'booking_id' => $booking->id,
    ]);

    expect((float) $giftCard->fresh()->current_balance)->toBe(0.0);
});
