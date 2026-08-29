<?php

declare(strict_types=1);

namespace App\Domain\Venues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
            'layout' => ['nullable', 'array'],
            'layout.*.name' => ['required_with:layout', 'string', 'max:255'],
            'layout.*.sections' => ['required_with:layout', 'array'],
            'layout.*.sections.*.name' => ['required_with:layout.*.sections', 'string', 'max:255'],
            'layout.*.sections.*.rows' => ['required_with:layout.*.sections', 'integer', 'min:1', 'max:26'],
            'layout.*.sections.*.seats_per_row' => ['required_with:layout.*.sections', 'integer', 'min:1', 'max:50'],
            'layout.*.sections.*.seat_type' => ['nullable', 'string', 'in:standard,vip,premium,wheelchair'],
            'layout.*.sections.*.color' => ['nullable', 'string', 'max:7'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
