<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Domain\Organizations\Models\Organization;
use App\Models\User;
use App\Shared\Traits\BelongsToOrganization;
use Database\Factories\GiftCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCard extends Model
{
    /** @use HasFactory<GiftCardFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'issued_by_user_id',
        'code',
        'original_balance',
        'current_balance',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'original_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function hasBalance(): bool
    {
        return $this->is_active && (float) $this->current_balance > 0;
    }

    public function canCover(float $amount): bool
    {
        return $this->hasBalance() && (float) $this->current_balance >= $amount;
    }
}
