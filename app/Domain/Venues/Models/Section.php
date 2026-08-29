<?php

declare(strict_types=1);

namespace App\Domain\Venues\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected $fillable = [
        'hall_id',
        'name',
        'section_type',
        'color',
        'capacity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Hall, $this> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /** @return HasMany<Row, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(Row::class)->orderBy('sort_order');
    }

    /** @return Collection<int, Seat> */
    public function seats(): Collection
    {
        return Seat::whereIn('row_id', $this->rows()->pluck('id'))->get();
    }

    /**
     * Generate a grid of rows and seats in this section.
     *
     * @param  array{rows: int, seats_per_row: int, seat_type?: string}  $grid
     */
    public function generateGrid(array $grid): void
    {
        $seatType = $grid['seat_type'] ?? 'standard';

        for ($r = 0; $r < $grid['rows']; $r++) {
            $rowLabel = chr(65 + $r); // A, B, C, ...

            $row = $this->rows()->create([
                'label' => $rowLabel,
                'sort_order' => $r,
            ]);

            for ($s = 0; $s < $grid['seats_per_row']; $s++) {
                $row->seats()->create([
                    'seat_number' => $s + 1,
                    'type' => $seatType,
                    'row_position' => $r,
                    'col_position' => $s,
                    'x_coord' => $s * 40.0,
                    'y_coord' => $r * 40.0,
                ]);
            }
        }
    }
}
