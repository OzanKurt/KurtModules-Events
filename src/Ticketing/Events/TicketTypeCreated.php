<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class TicketTypeCreated
{
    use Dispatchable;

    public function __construct(public readonly TicketType $type) {}
}
