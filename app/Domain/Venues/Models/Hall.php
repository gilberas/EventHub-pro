<?php

declare(strict_types=1);

namespace App\Domain\Venues\Models;

use Database\Factories\HallFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hall extends Model
{
    /** @use HasFactory<HallFactory> */
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'name',
        'description',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    /** @return Collection<int, Seat> */
    public function seats(): Collection
    {
        return Seat::whereIn('row_id', $this->rows()->pluck('id'))->get();
    }

    /** @return HasMany<Row, $this> */
    public function rows(): HasMany
    {
        return $this->hasManyThrough(Row::class, Section::class);
    }
}
