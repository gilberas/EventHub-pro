<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Events;

use App\Domain\Organizations\Models\Organization;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Organization $organization,
    ) {}
}
