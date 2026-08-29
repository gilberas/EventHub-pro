<?php

declare(strict_types=1);

namespace App\Domain\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinWaitingListRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
        ];
    }
}
