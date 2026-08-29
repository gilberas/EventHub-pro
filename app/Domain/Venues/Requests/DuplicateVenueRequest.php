<?php

declare(strict_types=1);

namespace App\Domain\Venues\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateVenueRequest extends FormRequest
{
    /** @return array{} */
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}
