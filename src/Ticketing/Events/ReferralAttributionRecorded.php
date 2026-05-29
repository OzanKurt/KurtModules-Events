<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\ReferralLink;

final class ReferralAttributionRecorded
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly ReferralLink $link,
    ) {}
}
