<?php

declare(strict_types=1);

namespace App\Domain\Venues\Models;

use App\Domain\Organizations\Models\Organization;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'country',
        'zip',
        'latitude',
        'longitude',
        'phone',
        'website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<Hall, $this> */
    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class)->orderBy('name');
    }

    public static function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (true) {
            $query = static::where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function totalSeats(): int
    {
        return $this->halls->sum(fn (Hall $hall) => $hall->seats()->count());
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }
}
