<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')->ignore($this->route('organization')),
            ],
            'domain' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('organizations', 'domain')->ignore($this->route('organization')),
            ],
            'settings' => ['nullable', 'json'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'refund_policy_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'refund_policy_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
        ];
    }
}
