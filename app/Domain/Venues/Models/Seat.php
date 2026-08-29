<?php

declare(strict_types=1);

namespace App\Domain\Venues\Models;

use App\Shared\Enums\SeatType;
use Database\Factories\SeatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    /** @use HasFactory<SeatFactory> */
    use HasFactory;

    protected $fillable = [
        'row_id',
        'seat_number',
        'type',
        'row_position',
        'col_position',
        'x_coord',
        'y_coord',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SeatType::class,
            'seat_number' => 'integer',
            'row_position' => 'integer',
            'col_position' => 'integer',
            'x_coord' => 'float',
            'y_coord' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Row, $this> */
    public function row(): BelongsTo
    {
        return $this->belongsTo(Row::class);
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->through('row');
    }

    public function label(): string
    {
        return $this->row->label.$this->seat_number;
    }
}
