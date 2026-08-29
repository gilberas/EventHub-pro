<?php

declare(strict_types=1);

namespace App\Domain\Organizations\DTOs;

use App\Domain\Organizations\Models\Organization;

class OrganizationDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $domain,
        public readonly ?string $logo_url,
        /** @var array<string, mixed>|null */
        public readonly ?array $settings,
        public readonly string $subscription_plan,
        public readonly string $timezone,
        public readonly string $currency,
        public readonly ?string $billing_email,
        public readonly ?string $billing_address,
        public readonly ?int $refund_policy_days,
        public readonly ?float $refund_policy_percentage,
        public readonly ?string $stripe_customer_id,
        public readonly bool $is_active,
    ) {}

    public static function fromModel(Organization $organization): self
    {
        return new self(
            id: $organization->id,
            name: $organization->name,
            slug: $organization->slug,
            domain: $organization->domain,
            logo_url: $organization->logoUrl(),
            settings: $organization->settings,
            subscription_plan: $organization->subscription_plan,
            timezone: $organization->timezone,
            currency: $organization->currency,
            billing_email: $organization->billing_email,
            billing_address: $organization->billing_address,
            refund_policy_days: $organization->refund_policy_days,
            refund_policy_percentage: $organization->refund_policy_percentage,
            stripe_customer_id: $organization->stripe_customer_id,
            is_active: $organization->is_active,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'logo_url' => $this->logo_url,
            'settings' => $this->settings,
            'subscription_plan' => $this->subscription_plan,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'billing_email' => $this->billing_email,
            'billing_address' => $this->billing_address,
            'refund_policy_days' => $this->refund_policy_days,
            'refund_policy_percentage' => $this->refund_policy_percentage,
            'stripe_customer_id' => $this->stripe_customer_id,
            'is_active' => $this->is_active,
        ];
    }
}
