<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\SessionCheckIn;

final class SessionCheckedIn
{
    use Dispatchable;

    public function __construct(public readonly SessionCheckIn $checkIn) {}
}
