<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\MobileMoneyGateway;
use App\Domain\Payments\Gateways\MockGateway;
use App\Domain\Payments\Gateways\PayPalGateway;
use App\Domain\Payments\Gateways\StripeGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    private array $gateways = [];

    public function __construct()
    {
        $this->register('stripe', fn () => new StripeGateway);
        $this->register('paypal', fn () => new PayPalGateway);
        $this->register('mobile_money', fn () => new MobileMoneyGateway);
        $this->register('mock', fn () => new MockGateway);
    }

    public function register(string $name, callable $factory): void
    {
        $this->gateways[$name] = $factory;
    }

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('services.payment.gateway', 'stripe');

        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not supported.");
        }

        $factory = $this->gateways[$name];

        return $factory();
    }

    public function names(): array
    {
        return array_keys($this->gateways);
    }
}
