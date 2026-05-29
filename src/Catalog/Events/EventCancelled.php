<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Catalog\Models\Event;

final class EventCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly Event $event,
        public readonly Model $canceller,
        public readonly string $reason,
    ) {}
}
