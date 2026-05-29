<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventTemplate;

final class EventCreatedFromTemplate
{
    use Dispatchable;

    public function __construct(
        public readonly Event $event,
        public readonly EventTemplate $template,
    ) {}
}
