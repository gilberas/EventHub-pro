<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Events\Models\Event;
use App\Domain\Organizations\Models\Organization;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /** @return BelongsToMany<Organization, $this> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withTimestamps();
    }

    /** @return BelongsTo<Organization, $this> */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    /** @return BelongsToMany<Event, $this> */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'favorite_events')
            ->withPivot('created_at');
    }

    public function currentOrganizationId(): ?int
    {
        $sessionOrgId = session('current_organization_id');

        if (! empty($sessionOrgId)) {
            return (int) $sessionOrgId;
        }

        $firstOrganizationId = $this->organizations()->value('organizations.id');

        if ($firstOrganizationId !== null) {
            session(['current_organization_id' => (int) $firstOrganizationId]);

            return (int) $firstOrganizationId;
        }

        return null;
    }

    /** @return list<array{id: int, name: string, organization_id: int|null}> */
    public function getRoles(): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $this->id)
            ->where('model_has_roles.model_type', get_class($this))
            ->get(['roles.id', 'roles.name', 'roles.organization_id'])
            ->map(fn ($row): array => (array) $row)
            ->toArray();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SuperAdministrator');
    }

    public function isPlatformAdmin(): bool
    {
        return $this->hasRole('PlatformAdmin');
    }

    public function switchOrganization(Organization $org): void
    {
        session(['current_organization_id' => $org->id]);
    }
}
