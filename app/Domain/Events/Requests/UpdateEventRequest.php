<?php

declare(strict_types=1);

namespace App\Domain\Events\Requests;

use App\Shared\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'category' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['sometimes', Rule::in(array_column(EventStatus::cases(), 'value'))],
            'age_restriction' => ['nullable', 'integer', 'min:0', 'max:255'],
            'terms' => ['nullable', 'string', 'max:65535'],
            'refund_policy_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'refund_policy_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_featured' => ['sometimes', 'boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ];
    }
}
