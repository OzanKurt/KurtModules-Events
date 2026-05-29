<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

final class TicketTransferred
{
    use Dispatchable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?int $oldHolderId,
        public readonly Model $newHolder,
    ) {}
}
