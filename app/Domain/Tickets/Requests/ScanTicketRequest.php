<?php

declare(strict_types=1);

namespace App\Domain\Tickets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payload' => 'required|string',
            'event_session_id' => 'required|integer|exists:event_sessions,id',
        ];
    }
}
