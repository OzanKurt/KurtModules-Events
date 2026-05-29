<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Catalog\Models\EventTemplate;

final class EventTemplateSaved
{
    use Dispatchable;

    public function __construct(public readonly EventTemplate $template) {}
}
