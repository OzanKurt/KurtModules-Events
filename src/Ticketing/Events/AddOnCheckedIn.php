<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOnPurchase;

final class AddOnCheckedIn
{
    use Dispatchable;

    public function __construct(
        public readonly TicketAddOnPurchase $purchase,
        public readonly Model $scanner,
    ) {}
}
