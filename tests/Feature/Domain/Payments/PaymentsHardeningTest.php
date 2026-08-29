<?php

declare(strict_types=1);

use App\Domain\Bookings\Models\Booking;
use App\Domain\Bookings\Models\BookingItem;
use App\Domain\Bookings\Models\TicketType;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\EventSession;
use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Services\InvoiceService;
use App\Domain\Payments\Services\PaymentGatewayManager;
use App\Domain\Payments\Services\RefundWorkflow;
use App\Models\User;
use App\Shared\Enums\BookingStatus;
use App\Shared\Enums\RefundStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    config(['services.payment.gateway' => 'stripe']);
    config(['services.stripe.secret' => 'test_secret']);
});

// ---- PaymentGatewayManager ----

it('resolves the default stripe driver', function () {
    $manager = app(PaymentGatewayManager::class);
    $gateway = $manager->driver();
    expect($gateway)->toBeInstanceOf(PaymentGateway::class);
    expect($gateway->name())->toBe('stripe');
});

it('resolves a named driver', function () {
    $manager = app(PaymentGatewayManager::class);
    expect($manager->driver('paypal')->name())->toBe('paypal');
    expect($manager->driver('mobile_money')->name())->toBe('mobile_money');
});

it('lists all registered gateway names', function () {
    $manager = app(PaymentGatewayManager::class);
    $names = $manager->names();
    expect($names)->toContain('stripe', 'paypal', 'mobile_money');
});

it('throws for unsupported gateway', function () {
    $manager = app(PaymentGatewayManager::class);
    $manager->driver('bitcoin');
})->throws(InvalidArgumentException::class);

// ---- RefundWorkflow::reject ----

it('rejects a pending refund request', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');

    $workflow = new RefundWorkflow($gateway);
    $request = $workflow->requestRefund($booking, $user, 50.0, 'Not satisfied');

    $rejected = $workflow->reject($request, $reviewer, 'Policy violation');

    expect($rejected->status)->toBe(RefundStatus::Rejected);
    expect($rejected->reviewed_by_user_id)->toBe($reviewer->id);
    expect($rejected->review_notes)->toBe('Policy violation');
    expect($rejected->reviewed_at)->not->toBeNull();
});

it('rejects a refund request with null notes', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('name')->andReturn('stripe');

    $workflow = new RefundWorkflow($gateway);
    $request = $workflow->requestRefund($booking, $user, 25.0, 'Too expensive');

    $rejected = $workflow->reject($request, $reviewer);
    expect($rejected->status)->toBe(RefundStatus::Rejected);
    expect($rejected->review_notes)->toBeNull();
});

// ---- InvoiceService ----

it('generates an invoice for a confirmed booking', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create();
    $event = Event::factory()->create(['organization_id' => $org->id]);
    $session = EventSession::factory()->create(['event_id' => $event->id]);
    $tt = TicketType::factory()->create(['event_session_id' => $session->id, 'price' => 50]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
        'subtotal' => 150,
        'fees' => 7.5,
        'total' => 157.5,
    ]);
    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'ticket_type_id' => $tt->id,
        'quantity' => 3,
        'unit_price' => 50,
        'subtotal' => 150,
    ]);

    Illuminate\Support\Facades\Event::fake();
    $service = app(InvoiceService::class);
    $invoice = $service->generate($booking, 10.0);

    expect($invoice->booking_id)->toBe($booking->id);
    expect($invoice->number)->not->toBeNull();
    expect($invoice->status)->toBe('issued');
    expect((float) $invoice->subtotal)->toBe(150.0);
    expect((float) $invoice->discount_total)->toBe(10.0);
    expect((float) $invoice->total)->toBe(157.5);
});
