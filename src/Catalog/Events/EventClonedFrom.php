<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Catalog\Models\Event;

final class EventClonedFrom
{
    use Dispatchable;

    public function __construct(
        public readonly Event $source,
        public readonly Event $clone,
    ) {}
}
