<?php

declare(strict_types=1);

namespace App\Domain\Events\Requests;

use App\Shared\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
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
            'sessions' => ['nullable', 'array'],
            'sessions.*.title' => ['nullable', 'string', 'max:255'],
            'sessions.*.start_date' => ['required_with:sessions', 'date'],
            'sessions.*.end_date' => ['required_with:sessions', 'date', 'after:sessions.*.start_date'],
            'sessions.*.location' => ['nullable', 'string', 'max:255'],
            'sessions.*.capacity' => ['nullable', 'integer', 'min:0'],
            'sessions.*.recurrence_rule' => ['nullable', 'string', 'max:500'],
            'sessions.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sessions.*.venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'sessions.*.ticket_types' => ['nullable', 'array', 'min:1'],
            'sessions.*.ticket_types.*.name' => ['required', 'string', 'max:255'],
            'sessions.*.ticket_types.*.price' => ['required', 'numeric', 'min:0'],
            'sessions.*.ticket_types.*.description' => ['nullable', 'string', 'max:1000'],
            'sessions.*.ticket_types.*.mode' => [
                'required',
                Rule::in(['general_admission', 'reserved']),
            ],
            'sessions.*.ticket_types.*.quantity_available' => ['nullable', 'integer', 'min:0'],
            'sessions.*.ticket_types.*.max_per_order' => ['nullable', 'integer', 'min:1'],
            'sessions.*.ticket_types.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
