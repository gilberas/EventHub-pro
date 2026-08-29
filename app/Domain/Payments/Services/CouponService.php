<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Models\Coupon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function findByCode(string $code, Organization $organization): ?Coupon
    {
        return Coupon::where('code', $code)
            ->where('organization_id', $organization->id)
            ->first();
    }

    public function validateCoupon(Coupon $coupon, float $orderAmount): array
    {
        if (! $coupon->isValid($orderAmount)) {
            return [
                'valid' => false,
                'error' => 'This coupon is no longer valid or has expired.',
            ];
        }

        $discount = $coupon->calculateDiscount($orderAmount);

        return [
            'valid' => true,
            'discount' => $discount,
            'coupon' => $coupon,
        ];
    }

    public function apply(Coupon $coupon, float $orderAmount): float
    {
        return DB::transaction(function () use ($coupon, $orderAmount) {
            $locked = Coupon::lockForUpdate()->find($coupon->id);

            if (! $locked || ! $locked->isValid($orderAmount)) {
                throw new \RuntimeException('Coupon is no longer valid.');
            }

            $discount = $locked->calculateDiscount($orderAmount);
            $locked->increment('current_uses');

            return $discount;
        });
    }
}
