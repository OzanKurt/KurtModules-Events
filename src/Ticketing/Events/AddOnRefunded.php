<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOnPurchase;

final class AddOnRefunded
{
    use Dispatchable;

    public function __construct(public readonly TicketAddOnPurchase $purchase) {}
}
