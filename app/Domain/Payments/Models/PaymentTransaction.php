<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Shared\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;

class PaymentTransaction extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'payable_type',
        'payable_id',
        'gateway',
        'transaction_id',
        'type',
        'amount',
        'currency',
        'status',
        'error_message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
