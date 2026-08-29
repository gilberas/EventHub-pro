<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Requests;

use App\Shared\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class InviteStaffRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $orgRoles = collect(RoleEnum::cases())
            ->filter(fn (RoleEnum $role) => $role->isOrganizationRole())
            ->map(fn (RoleEnum $role) => $role->name)
            ->values()
            ->toArray();

        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:'.implode(',', $orgRoles)],
        ];
    }
}
