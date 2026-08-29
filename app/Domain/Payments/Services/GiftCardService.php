<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Organizations\Models\Organization;
use App\Domain\Payments\Models\GiftCard;
use Illuminate\Support\Facades\DB;

class GiftCardService
{
    public function findByCode(string $code, Organization $organization): ?GiftCard
    {
        return GiftCard::where('code', $code)
            ->where('organization_id', $organization->id)
            ->first();
    }

    public function validateGiftCard(GiftCard $giftCard, float $orderAmount): array
    {
        if (! $giftCard->hasBalance()) {
            return [
                'valid' => false,
                'error' => 'This gift card has no remaining balance.',
            ];
        }

        if ($giftCard->expires_at && $giftCard->expires_at->isPast()) {
            return [
                'valid' => false,
                'error' => 'This gift card has expired.',
            ];
        }

        $amountToUse = min((float) $giftCard->current_balance, $orderAmount);

        return [
            'valid' => true,
            'amount_to_use' => $amountToUse,
            'remaining_balance' => (float) $giftCard->current_balance - $amountToUse,
            'gift_card' => $giftCard,
        ];
    }

    public function redeem(GiftCard $giftCard, float $amount): float
    {
        return DB::transaction(function () use ($giftCard, $amount) {
            $locked = GiftCard::lockForUpdate()->find($giftCard->id);

            if (! $locked || ! $locked->hasBalance()) {
                throw new \RuntimeException('Gift card has no balance.');
            }

            $actualAmount = min($amount, (float) $locked->current_balance);
            $locked->decrement('current_balance', $actualAmount);

            return $actualAmount;
        });
    }
}
