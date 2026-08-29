<?php

declare(strict_types=1);

namespace App\Domain\Payments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'hold_ids' => 'required|array|min:1',
            'hold_ids.*' => 'integer|exists:seat_holds,id',
            'gateway' => 'nullable|string|in:stripe,paypal,mobile_money,mock',
            'payment.payment_method_id' => 'nullable|string',
            'payment.return_url' => 'nullable|url',
            'payment.cancel_url' => 'nullable|url',
            'payment.payer_id' => 'nullable|string',
            'payment.payment_id' => 'nullable|string',
            'coupon_code' => 'nullable|string|max:50',
            'gift_card_code' => 'nullable|string|max:50',
        ];
    }
}
