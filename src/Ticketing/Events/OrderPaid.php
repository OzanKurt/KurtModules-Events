<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\Order;

final class OrderPaid
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
