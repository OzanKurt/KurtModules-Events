<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

final class QueueJoined
{
    use Dispatchable;

    public function __construct(public readonly SaleQueueEntry $entry) {}
}
