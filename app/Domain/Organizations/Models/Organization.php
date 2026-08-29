<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Models;

use App\Models\User;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo_path
 * @property string|null $domain
 * @property array<string, mixed>|null $settings
 * @property string $subscription_plan
 * @property string $timezone
 * @property string $currency
 * @property string|null $billing_email
 * @property string|null $billing_address
 * @property int|null $refund_policy_days
 * @property float|null $refund_policy_percentage
 * @property string|null $stripe_customer_id
 * @property Carbon|null $trial_ends_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, User> $users
 * @property-read User|null $owner
 */
class Organization extends Model implements HasMedia
{
    /** @use HasFactory<OrganizationFactory> */
    use BelongsToOrganization, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'settings',
        'subscription_plan',
        'timezone',
        'currency',
        'billing_email',
        'billing_address',
        'refund_policy_days',
        'refund_policy_percentage',
        'stripe_customer_id',
        'trial_ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'refund_policy_days' => 'integer',
            'refund_policy_percentage' => 'decimal:2',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useDisk('public');
    }

    public function logoUrl(): ?string
    {
        $media = $this->getFirstMedia('logo');

        return $media?->getFullUrl();
    }

    /** @return HasMany<StaffInvitation, $this> */
    public function staffInvitations(): HasMany
    {
        return $this->hasMany(StaffInvitation::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withTimestamps();
    }

    public function owner(): ?User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->id);

        $role = User::role('OrganizationOwner')->first();

        return $role;
    }

    public function isOwnedBy(User $user): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->id);

        return $user->hasRole('OrganizationOwner');
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /** @return Collection<int, User> */
    public function staff(): Collection
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->id);

        return User::role([
            'OrganizationAdmin',
            'EventManager',
            'FinanceManager',
            'SupportAgent',
            'TicketScanner',
        ])->get();
    }

    public function staffCount(): int
    {
        return $this->staff()->count();
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }
}
